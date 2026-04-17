<?php
/**
 * Public AI Assistant chatbot widget for the vendor's public profile page.
 * Provides customer-facing AI that can answer questions and book appointments.
 *
 * Requires $vendor array with 'slug' and 'business_name' keys.
 */
$chatUrl = base_url('p/' . e($vendor['slug']) . '/ai/chat');
$executeUrl = base_url('p/' . e($vendor['slug']) . '/ai/execute');
$businessName = e($vendor['business_name'] ?? 'Estabelecimento');
$liaAvatarUrl = asset('assets/img/lia-avatar.svg');
?>
<div id="pub-ai-assistant" class="ai-chat ai-chat--public" aria-label="Assistente Virtual">
    <div class="ai-chat__backdrop" id="pub-ai-backdrop"></div>

    <button type="button" class="ai-chat__toggle ai-chat__toggle--public" id="pub-ai-toggle" aria-label="Abrir assistente virtual" aria-expanded="false">
        <span class="ai-chat__toggle-media" aria-hidden="true">
            <img class="ai-chat__toggle-image" src="<?= e($liaAvatarUrl) ?>" alt="" loading="lazy" decoding="async">
        </span>
        <span class="ai-chat__toggle-text">Lia</span>
    </button>

    <div class="ai-chat__panel" id="pub-ai-panel" hidden>
        <div class="ai-chat__header ai-chat__header--public">
            <div class="ai-chat__header-info">
                <span class="ai-chat__avatar">
                    <img class="ai-chat__avatar-image" src="<?= e($liaAvatarUrl) ?>" alt="Lia" loading="lazy" decoding="async">
                </span>
                <div>
                    <strong class="ai-chat__header-title">Lia</strong>
                    <span class="ai-chat__status">Assistente Virtual</span>
                </div>
            </div>
            <button type="button" class="ai-chat__close" id="pub-ai-close" aria-label="Fechar">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>

        <div class="ai-chat__messages" id="pub-ai-messages">
            <div class="ai-chat__msg ai-chat__msg--bot">
                <span class="ai-chat__msg-avatar">
                    <img class="ai-chat__msg-avatar-image" src="<?= e($liaAvatarUrl) ?>" alt="Lia" loading="lazy" decoding="async">
                </span>
                <div class="ai-chat__msg-bubble">
                    Olá! Sou a Lia, assistente virtual de <strong><?= $businessName ?></strong>. Posso te mostrar serviços, avaliações, localização e até abrir a agenda para agendar. Como posso ajudar? 😊
                </div>
            </div>
        </div>

        <div class="ai-chat__action-bar" id="pub-ai-action-bar" hidden>
            <span id="pub-ai-action-label">Confirmar agendamento?</span>
            <div class="ai-chat__action-buttons">
                <button type="button" class="btn btn-sm" id="pub-ai-action-confirm">✅ Confirmar</button>
                <button type="button" class="btn btn-sm btn-light" id="pub-ai-action-cancel">Cancelar</button>
            </div>
        </div>

        <form class="ai-chat__input-area" id="pub-ai-form" autocomplete="off">
            <input type="text" class="ai-chat__input" id="pub-ai-input" placeholder="Digite ou fale sua pergunta..." maxlength="500" aria-label="Mensagem">
            <button type="button" class="ai-chat__mic" id="pub-ai-mic" aria-label="Gravar mensagem por voz" title="Falar por voz">
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
    const toggle = document.getElementById('pub-ai-toggle');
    const panel = document.getElementById('pub-ai-panel');
    const closeBtn = document.getElementById('pub-ai-close');
    const backdrop = document.getElementById('pub-ai-backdrop');
    const form = document.getElementById('pub-ai-form');
    const input = document.getElementById('pub-ai-input');
    const messagesEl = document.getElementById('pub-ai-messages');
    const actionBar = document.getElementById('pub-ai-action-bar');
    const actionLabel = document.getElementById('pub-ai-action-label');
    const actionConfirm = document.getElementById('pub-ai-action-confirm');
    const actionCancel = document.getElementById('pub-ai-action-cancel');

    const chatUrl = <?= json_encode($chatUrl) ?>;
    const executeUrl = <?= json_encode($executeUrl) ?>;
    const avatarUrl = <?= json_encode($liaAvatarUrl) ?>;
    const botAvatarMarkup = '<span class="ai-chat__msg-avatar"><img class="ai-chat__msg-avatar-image" src="' + avatarUrl + '" alt="Lia"></span>';
    const MOBILE_BREAKPOINT = 600;
    const KEYBOARD_THRESHOLD = 70;

    let history = [];
    let pendingAction = null;
    let isOpen = false;
    let isSending = false;

    function isMobile() {
        return window.innerWidth <= MOBILE_BREAKPOINT;
    }

    function isIOS() {
        return /iPad|iPhone|iPod/.test(window.navigator.userAgent) || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
    }

    function syncViewportLayout() {
        if (!window.visualViewport) {
            return;
        }

        const viewportHeight = Math.round(window.visualViewport.height);
        panel.style.setProperty('--ai-viewport-height', viewportHeight + 'px');

        if (!isOpen || !isMobile()) {
            panel.style.height = '';
            panel.style.maxHeight = '';
            panel.style.bottom = '';
            return;
        }

        const keyboardOffset = Math.max(0, window.innerHeight - window.visualViewport.height - window.visualViewport.offsetTop);
        if (keyboardOffset > KEYBOARD_THRESHOLD) {
            const sheetHeight = Math.max(300, Math.round(window.visualViewport.height - 8));
            panel.style.height = sheetHeight + 'px';
            panel.style.maxHeight = sheetHeight + 'px';
            panel.style.bottom = '0px';
        } else {
            panel.style.height = '';
            panel.style.maxHeight = '';
            panel.style.bottom = '';
        }

        scrollToBottom();
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
        syncViewportLayout();
        if (!isMobile()) {
            input.focus();
        }
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
        panel.style.height = '';
        panel.style.maxHeight = '';
        panel.style.bottom = '';
        input.blur();
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function addMessage(role, text) {
        const div = document.createElement('div');
        div.className = 'ai-chat__msg ai-chat__msg--' + (role === 'user' ? 'user' : 'bot');

        const avatar = document.createElement('span');
        avatar.className = 'ai-chat__msg-avatar';
        if (role === 'user') {
            avatar.textContent = '👤';
        } else {
            avatar.innerHTML = '<img class="ai-chat__msg-avatar-image" src="' + avatarUrl + '" alt="Lia">';
        }

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
        div.id = 'pub-ai-typing';
        div.innerHTML = botAvatarMarkup + '<div class="ai-chat__msg-bubble"><span class="ai-chat__dots"><span>.</span><span>.</span><span>.</span></span></div>';
        messagesEl.appendChild(div);
        scrollToBottom();
    }

    function removeTypingIndicator() {
        const typing = document.getElementById('pub-ai-typing');
        if (typing) typing.remove();
    }

    function formatMarkdown(text) {
        let result = text
            .replace(/```json\s*\{(?:[^{}]|\{[^{}]*\})*\}\s*```/g, '')
            .replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>');

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

        if (type === 'create_appointment') {
            const date = data.appointment_date || '';
            const time = data.start_time || '';
            label = '📅 Confirmar agendamento para ' + (data.customer_name || 'você') + ' em ' + date + ' às ' + time + '?';
        } else if (type === 'check_available_slots') {
            label = '🕐 Consultar horários disponíveis?';
        } else if (type === 'navigate') {
            label = '🔗 Abrir ' + (data.label || 'esta área') + '?';
        }

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
                if (!isMobile()) {
                    input.focus();
                }
                return;
            }

            const data = await res.json();

            if (data.action && data.action.action === 'navigate') {
                addMessage('bot', data.reply);
                history.push({ role: 'assistant', content: data.reply });
                const target = data.action.data && data.action.data.url;
                if (target) {
                    window.setTimeout(() => { window.location.href = target; }, 500);
                }
                input.disabled = false;
                isSending = false;
                return;
            }

            addMessage('bot', data.reply);
            history.push({ role: 'assistant', content: data.reply });

            if (history.length > 20) {
                history = history.slice(-16);
            }

            if (data.action) {
                if (data.action.action === 'check_available_slots') {
                    pendingAction = data.action;
                    await confirmAction();
                } else {
                    showActionBar(data.action);
                }
            }
        } catch (err) {
            removeTypingIndicator();
            addMessage('bot', '❌ Erro de conexão. Verifique sua internet e tente novamente.');
        }

        input.disabled = false;
        isSending = false;
        if (!isMobile()) {
            input.focus();
        }
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
                addMessage('bot', data.result || '✅ Ação realizada com sucesso!');
            } else {
                addMessage('bot', '❌ Erro ao processar. Tente novamente.');
            }
        } catch {
            removeTypingIndicator();
            addMessage('bot', '❌ Erro de conexão.');
        }
    }

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

    const pubMicBtn = document.getElementById('pub-ai-mic');
    if (pubMicBtn) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            let recognition = null;
            let isRecording = false;

            pubMicBtn.addEventListener('click', function() {
                if (isRecording) {
                    if (recognition) recognition.stop();
                    return;
                }

                recognition = new SpeechRecognition();
                recognition.lang = 'pt-BR';
                recognition.interimResults = !isIOS();
                recognition.maxAlternatives = 1;
                recognition.continuous = false;

                input.blur();

                recognition.onstart = function() {
                    isRecording = true;
                    pubMicBtn.classList.add('ai-chat__mic--recording');
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
                    pubMicBtn.classList.remove('ai-chat__mic--recording');
                    input.placeholder = 'Digite ou fale sua pergunta...';
                    syncViewportLayout();
                    const text = input.value.trim();
                    if (text && !isSending) sendMessage(text);
                };

                recognition.onerror = function(event) {
                    isRecording = false;
                    pubMicBtn.classList.remove('ai-chat__mic--recording');
                    input.placeholder = 'Digite ou fale sua pergunta...';
                    if (event.error === 'not-allowed') {
                        addMessage('bot', '🎤 Permita o acesso ao microfone para usar o comando de voz.');
                    }
                };

                recognition.start();
            });
        } else {
            pubMicBtn.addEventListener('click', () => {
                addMessage('bot', '🎤 O comando de voz não está disponível neste navegador.');
            });
        }
    }

    actionConfirm.addEventListener('click', confirmAction);
    actionCancel.addEventListener('click', () => {
        hideActionBar();
        addMessage('bot', '🚫 Ação cancelada. Como mais posso ajudar?');
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) closeChat();
    });

    document.addEventListener('click', (e) => {
        if (isOpen && !panel.contains(e.target) && !toggle.contains(e.target)) {
            closeChat();
        }
    });

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', syncViewportLayout);
        window.visualViewport.addEventListener('scroll', syncViewportLayout);
    }

    input.addEventListener('focus', () => {
        window.setTimeout(syncViewportLayout, 120);
    });
    input.addEventListener('blur', () => {
        window.setTimeout(syncViewportLayout, 120);
    });
})();
</script>
