<?php
/**
 * AI Assistant floating chatbot widget.
 * Included in the vendor layout — appears as a floating button on all vendor pages.
 */
?>
<div id="ai-assistant" class="ai-chat" aria-label="Assistente IA">
    <!-- Backdrop for mobile bottom sheet -->
    <div class="ai-chat__backdrop" id="ai-backdrop"></div>

    <!-- Floating toggle button -->
    <button type="button" class="ai-chat__toggle" id="ai-toggle" aria-label="Abrir assistente IA" aria-expanded="false">
        <svg class="ai-chat__toggle-icon" viewBox="0 0 24 24" fill="none" width="26" height="26">
            <path d="M12 2a7 7 0 0 1 7 7v1a4 4 0 0 1-4 4h-1.5l-2.5 3v-3H10a4 4 0 0 1-4-4V9a7 7 0 0 1 6-6.93" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="9" cy="9" r="1" fill="currentColor"/>
            <circle cx="15" cy="9" r="1" fill="currentColor"/>
            <path d="M9 12s1.5 2 3 2 3-2 3-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span class="ai-chat__toggle-label">IA</span>
    </button>

    <!-- Chat panel -->
    <div class="ai-chat__panel" id="ai-panel" hidden>
        <div class="ai-chat__header">
            <div class="ai-chat__header-info">
                <span class="ai-chat__avatar">🤖</span>
                <div>
                    <strong>Lia</strong>
                    <span class="ai-chat__status">Online</span>
                </div>
            </div>
            <button type="button" class="ai-chat__close" id="ai-close" aria-label="Fechar">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>

        <div class="ai-chat__messages" id="ai-messages">
            <div class="ai-chat__msg ai-chat__msg--bot">
                <span class="ai-chat__msg-avatar">🤖</span>
                <div class="ai-chat__msg-bubble">
                    Oi! 👋 Sou a Lia, sua assistente no Apprumo. Posso gerenciar sua agenda, criar serviços, navegar pelas telas, enviar mensagens em massa e muito mais. Me diz, o que precisa?
                </div>
            </div>
        </div>

        <!-- Action confirmation bar (hidden by default) -->
        <div class="ai-chat__action-bar" id="ai-action-bar" hidden>
            <span id="ai-action-label">Criar serviço?</span>
            <div class="ai-chat__action-buttons">
                <button type="button" class="btn btn-sm" id="ai-action-confirm">✅ Confirmar</button>
                <button type="button" class="btn btn-sm btn-light" id="ai-action-cancel">Cancelar</button>
            </div>
        </div>

        <form class="ai-chat__input-area" id="ai-form" autocomplete="off">
            <input type="text" class="ai-chat__input" id="ai-input" placeholder="Digite ou fale sua mensagem..." maxlength="500" aria-label="Mensagem">
            <button type="button" class="ai-chat__mic" id="ai-mic" aria-label="Gravar mensagem por voz" title="Falar por voz">
                <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 10v2a7 7 0 0 1-14 0v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="19" x2="12" y2="23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="8" y1="23" x2="16" y2="23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
            <button type="submit" class="ai-chat__send" aria-label="Enviar">
                <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </form>
    </div>
</div>

<script>
(function() {
    const toggle = document.getElementById('ai-toggle');
    const panel = document.getElementById('ai-panel');
    const closeBtn = document.getElementById('ai-close');
    const backdrop = document.getElementById('ai-backdrop');
    const form = document.getElementById('ai-form');
    const input = document.getElementById('ai-input');
    const messagesEl = document.getElementById('ai-messages');
    const actionBar = document.getElementById('ai-action-bar');
    const actionLabel = document.getElementById('ai-action-label');
    const actionConfirm = document.getElementById('ai-action-confirm');
    const actionCancel = document.getElementById('ai-action-cancel');

    const chatUrl = <?= json_encode(base_url('vendor/ai/chat')) ?>;
    const executeUrl = <?= json_encode(base_url('vendor/ai/execute')) ?>;

    let history = [];
    let pendingAction = null;
    let isOpen = false;
    let isSending = false;

    function isMobile() {
        // Must match @media (max-width: 600px) breakpoint in app.css
        return window.innerWidth <= 600;
    }

    function openChat() {
        panel.hidden = false;
        isOpen = true;
        toggle.setAttribute('aria-expanded', 'true');
        toggle.classList.add('is-active');
        if (isMobile() && backdrop) {
            backdrop.classList.add('is-visible');
            document.body.style.overflow = 'hidden';
        }
        input.focus();
        scrollToBottom();
    }

    function closeChat() {
        panel.hidden = true;
        isOpen = false;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.classList.remove('is-active');
        if (backdrop) {
            backdrop.classList.remove('is-visible');
        }
        document.body.style.overflow = '';
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function addMessage(role, text) {
        const div = document.createElement('div');
        div.className = 'ai-chat__msg ai-chat__msg--' + (role === 'user' ? 'user' : 'bot');

        const avatar = document.createElement('span');
        avatar.className = 'ai-chat__msg-avatar';
        avatar.textContent = role === 'user' ? '👤' : '🤖';

        const bubble = document.createElement('div');
        bubble.className = 'ai-chat__msg-bubble';
        bubble.innerHTML = formatMarkdown(text);

        div.appendChild(avatar);
        div.appendChild(bubble);
        messagesEl.appendChild(div);
        scrollToBottom();
    }

    function addTypingIndicator() {
        const div = document.createElement('div');
        div.className = 'ai-chat__msg ai-chat__msg--bot ai-chat__typing';
        div.id = 'ai-typing';
        div.innerHTML = '<span class="ai-chat__msg-avatar">🤖</span><div class="ai-chat__msg-bubble"><span class="ai-chat__dots"><span>.</span><span>.</span><span>.</span></span></div>';
        messagesEl.appendChild(div);
        scrollToBottom();
    }

    function removeTypingIndicator() {
        const typing = document.getElementById('ai-typing');
        if (typing) typing.remove();
    }

    function formatMarkdown(text) {
        // Basic markdown: bold, italic, lists, code blocks
        let result = text
            .replace(/```json\s*\{(?:[^{}]|\{[^{}]*\})*\}\s*```/g, '') // Hide raw JSON actions
            .replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>');

        // Process list items: group consecutive bullet lines into a single <ul>
        const lines = result.split('\n');
        const output = [];
        let inList = false;
        for (const line of lines) {
            const match = line.match(/^[•\-]\s+(.+)/);
            if (match) {
                if (!inList) { output.push('<ul>'); inList = true; }
                output.push('<li>' + match[1] + '</li>');
            } else {
                if (inList) { output.push('</ul>'); inList = false; }
                output.push(line);
            }
        }
        if (inList) output.push('</ul>');

        return output.join('<br>').replace(/<br><ul>/g, '<ul>').replace(/<\/ul><br>/g, '</ul>');
    }

    function showActionBar(action) {
        pendingAction = action;
        const type = action.action || '';
        const data = action.data || {};
        let label = 'Executar ação?';

        const actionLabels = {
            // Services
            'create_service': () => '📋 Criar serviço "' + (data.title || '?') + '"?',
            'update_service': () => '📋 Atualizar serviço #' + (data.service_id || '?') + '?',
            'toggle_service': () => '📋 Ativar/desativar serviço #' + (data.service_id || '?') + '?',
            'delete_service': () => '🗑️ Excluir serviço #' + (data.service_id || '?') + '?',
            // Products
            'create_product': () => '📦 Criar produto "' + (data.name || '?') + '"?',
            'update_product': () => '📦 Atualizar produto #' + (data.product_id || '?') + '?',
            'delete_product': () => '🗑️ Excluir produto #' + (data.product_id || '?') + '?',
            'sell_product': () => '🛒 Registrar venda de ' + (data.quantity || 1) + 'x produto #' + (data.product_id || '?') + '?',
            // Appointments
            'create_appointment': () => {
                const date = data.appointment_date || '';
                const time = data.start_time || '';
                return '📅 Agendar "' + (data.customer_name || '?') + '" em ' + date + ' às ' + time + '?';
            },
            'update_appointment_status': () => {
                const statusLabels = {confirmed: 'Confirmado', completed: 'Concluído', cancelled: 'Cancelado', no_show: 'Não compareceu'};
                return '🔄 Alterar agendamento #' + (data.appointment_id || '?') + ' para "' + (statusLabels[data.status] || data.status || '?') + '"?';
            },
            'delete_appointment': () => '🗑️ Excluir agendamento #' + (data.appointment_id || '?') + '?',
            // Waiting list
            'create_waiting_entry': () => '⏳ Adicionar "' + (data.customer_name || '?') + '" na fila de espera?',
            'delete_waiting_entry': () => '⏳ Remover entrada #' + (data.entry_id || '?') + ' da fila de espera?',
            // Professionals
            'create_professional': () => '👤 Cadastrar profissional "' + (data.name || '?') + '"?',
            'update_professional': () => '👤 Atualizar profissional #' + (data.professional_id || '?') + '?',
            'toggle_professional': () => '👤 Ativar/desativar profissional #' + (data.professional_id || '?') + '?',
            'delete_professional': () => '🗑️ Excluir profissional #' + (data.professional_id || '?') + '?',
            'link_services_to_professional': () => '🔗 Vincular ' + ((data.service_ids || []).length || '?') + ' serviço(s) ao profissional #' + (data.professional_id || '?') + '?',
            // Queries (auto-execute)
            'check_available_slots': () => '🕐 Consultar horários disponíveis?',
            'list_appointments_for_date': () => '📅 Listar agendamentos do dia ' + (data.date || '?') + '?',
            'search_clients': () => '🔍 Buscar clientes: "' + (data.query || '?') + '"?',
            'get_finance_report': () => '💰 Gerar relatório financeiro?',
            'get_performance_report': () => '📊 Gerar relatório de desempenho?',
            'check_client_returns': () => '🔄 Consultar retornos do telefone ' + (data.phone || '?') + '?',
            // Settings
            'update_business_hours': () => '🕐 Atualizar horário de funcionamento?',
            // Navigation
            'navigate': () => '🔗 Navegar para ' + (data.label || data.url || 'outra tela') + '?',
            // Mass messaging
            'send_mass_message': () => '📨 Enviar mensagem em massa para ' + (data.filter === 'all' ? 'todos os clientes' : (data.filter || 'clientes selecionados')) + '?',
        };

        const labelFn = actionLabels[type];
        if (labelFn) label = labelFn();

        actionLabel.textContent = label;
        actionBar.hidden = false;
    }

    function hideActionBar() {
        pendingAction = null;
        actionBar.hidden = true;
    }

    async function sendMessage(text) {
        if (isSending) return;
        isSending = true;

        addMessage('user', text);
        history.push({ role: 'user', content: text });
        input.value = '';
        input.disabled = true;
        addTypingIndicator();

        try {
            const res = await fetch(chatUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text, history: history }),
            });

            removeTypingIndicator();

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                addMessage('bot', '❌ ' + (err.error || 'Erro ao processar. Tente novamente.'));
                input.disabled = false;
                isSending = false;
                input.focus();
                return;
            }

            const data = await res.json();

            // Handle navigation action — redirect without showing duplicate text
            if (data.action && data.action.action === 'navigate') {
                addMessage('bot', data.reply);
                history.push({ role: 'assistant', content: data.reply });
                const target = data.action.data && data.action.data.url;
                if (target) {
                    setTimeout(function() { window.location.href = target; }, 600);
                }
                input.disabled = false;
                isSending = false;
                return;
            }

            // Show action confirmation if AI returned an action
            if (data.action) {
                // Read-only query actions auto-execute without showing the
                // AI's intermediary text to avoid duplicate messages
                const readOnlyActions = ['check_available_slots', 'list_appointments_for_date', 'search_clients', 'get_finance_report', 'get_performance_report', 'check_client_returns'];
                if (readOnlyActions.includes(data.action.action)) {
                    // Only show the AI text if it doesn't have a query action
                    // (the action result will replace it)
                    history.push({ role: 'assistant', content: data.reply });
                    pendingAction = data.action;
                    addTypingIndicator();
                    await confirmAction();
                } else {
                    addMessage('bot', data.reply);
                    history.push({ role: 'assistant', content: data.reply });
                    showActionBar(data.action);
                }
            } else {
                addMessage('bot', data.reply);
                history.push({ role: 'assistant', content: data.reply });
            }

            // Keep history manageable
            if (history.length > 20) {
                history = history.slice(-16);
            }
        } catch (err) {
            removeTypingIndicator();
            addMessage('bot', '❌ Erro de conexão. Verifique sua internet e tente novamente.');
        }

        input.disabled = false;
        isSending = false;
        input.focus();
    }

    async function confirmAction() {
        if (!pendingAction) return;

        const action = pendingAction;
        hideActionBar();
        addTypingIndicator();

        try {
            const res = await fetch(executeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: action }),
            });

            removeTypingIndicator();

            if (res.ok) {
                const data = await res.json();
                addMessage('bot', data.result || '✅ Ação executada!');
            } else {
                addMessage('bot', '❌ Erro ao executar a ação.');
            }
        } catch {
            removeTypingIndicator();
            addMessage('bot', '❌ Erro de conexão.');
        }
    }

    // Event listeners
    toggle.addEventListener('click', () => isOpen ? closeChat() : openChat());
    closeBtn.addEventListener('click', closeChat);
    if (backdrop) {
        backdrop.addEventListener('click', closeChat);
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        if (isSending) return;
        const text = input.value.trim();
        if (text) sendMessage(text);
    });

    // Voice recognition (Web Speech API)
    const micBtn = document.getElementById('ai-mic');
    if (micBtn) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            let recognition = null;
            let isRecording = false;

            micBtn.addEventListener('click', function() {
                if (isRecording) {
                    if (recognition) recognition.stop();
                    return;
                }

                recognition = new SpeechRecognition();
                recognition.lang = 'pt-BR';
                recognition.interimResults = true;
                recognition.maxAlternatives = 1;
                recognition.continuous = false;

                recognition.onstart = function() {
                    isRecording = true;
                    micBtn.classList.add('ai-chat__mic--recording');
                    input.placeholder = '🎤 Ouvindo...';
                };

                recognition.onresult = function(event) {
                    let transcript = '';
                    for (let i = 0; i < event.results.length; i++) {
                        transcript += event.results[i][0].transcript;
                    }
                    input.value = transcript;
                };

                recognition.onend = function() {
                    isRecording = false;
                    micBtn.classList.remove('ai-chat__mic--recording');
                    input.placeholder = 'Digite ou fale sua mensagem...';
                    // Auto-send if we got text and not already sending
                    const text = input.value.trim();
                    if (text && !isSending) sendMessage(text);
                };

                recognition.onerror = function(event) {
                    isRecording = false;
                    micBtn.classList.remove('ai-chat__mic--recording');
                    input.placeholder = 'Digite ou fale sua mensagem...';
                    if (event.error === 'not-allowed') {
                        addMessage('bot', '🎤 Permita o acesso ao microfone nas configurações do navegador para usar o comando de voz.');
                    }
                };

                recognition.start();
            });
        } else {
            micBtn.style.display = 'none';
        }
    }

    actionConfirm.addEventListener('click', confirmAction);
    actionCancel.addEventListener('click', () => {
        hideActionBar();
        addMessage('bot', '🚫 Ação cancelada.');
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) closeChat();
    });

    // Close on click outside the panel
    document.addEventListener('click', (e) => {
        if (isOpen && !panel.contains(e.target) && !toggle.contains(e.target)) {
            closeChat();
        }
    });

    // iOS keyboard handling: adjust chat panel when virtual keyboard appears
    if (window.visualViewport) {
        let lastVpHeight = window.visualViewport.height;
        function handleViewportResize() {
            if (!isOpen || !isMobile()) return;
            var vpHeight = window.visualViewport.height;
            var offset = window.innerHeight - vpHeight;
            if (offset > 50) {
                // Keyboard is open — shrink panel and keep input visible
                panel.style.maxHeight = vpHeight + 'px';
                panel.style.bottom = '0px';
            } else {
                panel.style.maxHeight = '';
                panel.style.bottom = '';
            }
            scrollToBottom();
            lastVpHeight = vpHeight;
        }
        window.visualViewport.addEventListener('resize', handleViewportResize);
        window.visualViewport.addEventListener('scroll', handleViewportResize);
    }
})();
</script>
