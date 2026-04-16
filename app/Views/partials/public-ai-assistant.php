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
?>
<div id="pub-ai-assistant" class="ai-chat ai-chat--public" aria-label="Assistente Virtual">
    <!-- Floating toggle button -->
    <button type="button" class="ai-chat__toggle ai-chat__toggle--public" id="pub-ai-toggle" aria-label="Abrir assistente virtual" aria-expanded="false">
        <svg class="ai-chat__toggle-icon" viewBox="0 0 24 24" fill="none" width="26" height="26">
            <path d="M12 2a7 7 0 0 1 7 7v1a4 4 0 0 1-4 4h-1.5l-2.5 3v-3H10a4 4 0 0 1-4-4V9a7 7 0 0 1 6-6.93" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="9" cy="9" r="1" fill="currentColor"/>
            <circle cx="15" cy="9" r="1" fill="currentColor"/>
            <path d="M9 12s1.5 2 3 2 3-2 3-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span class="ai-chat__toggle-label">💬</span>
    </button>

    <!-- Chat panel -->
    <div class="ai-chat__panel" id="pub-ai-panel" hidden>
        <div class="ai-chat__header ai-chat__header--public">
            <div class="ai-chat__header-info">
                <span class="ai-chat__avatar">🤖</span>
                <div>
                    <strong><?= $businessName ?></strong>
                    <span class="ai-chat__status">Assistente Virtual</span>
                </div>
            </div>
            <button type="button" class="ai-chat__close" id="pub-ai-close" aria-label="Fechar">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>

        <div class="ai-chat__messages" id="pub-ai-messages">
            <div class="ai-chat__msg ai-chat__msg--bot">
                <span class="ai-chat__msg-avatar">🤖</span>
                <div class="ai-chat__msg-bubble">
                    Olá! 👋 Sou a assistente virtual de <strong><?= $businessName ?></strong>. Posso informar sobre nossos serviços, preços, horários e até agendar um horário para você. Como posso ajudar? 😊
                </div>
            </div>
        </div>

        <!-- Action confirmation bar (hidden by default) -->
        <div class="ai-chat__action-bar" id="pub-ai-action-bar" hidden>
            <span id="pub-ai-action-label">Confirmar agendamento?</span>
            <div class="ai-chat__action-buttons">
                <button type="button" class="btn btn-sm" id="pub-ai-action-confirm">✅ Confirmar</button>
                <button type="button" class="btn btn-sm btn-light" id="pub-ai-action-cancel">Cancelar</button>
            </div>
        </div>

        <form class="ai-chat__input-area" id="pub-ai-form" autocomplete="off">
            <input type="text" class="ai-chat__input" id="pub-ai-input" placeholder="Digite sua pergunta..." maxlength="500" aria-label="Mensagem">
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
    const form = document.getElementById('pub-ai-form');
    const input = document.getElementById('pub-ai-input');
    const messagesEl = document.getElementById('pub-ai-messages');
    const actionBar = document.getElementById('pub-ai-action-bar');
    const actionLabel = document.getElementById('pub-ai-action-label');
    const actionConfirm = document.getElementById('pub-ai-action-confirm');
    const actionCancel = document.getElementById('pub-ai-action-cancel');

    const chatUrl = <?= json_encode($chatUrl) ?>;
    const executeUrl = <?= json_encode($executeUrl) ?>;

    let history = [];
    let pendingAction = null;
    let isOpen = false;

    function openChat() {
        panel.hidden = false;
        isOpen = true;
        toggle.setAttribute('aria-expanded', 'true');
        toggle.classList.add('is-active');
        input.focus();
        scrollToBottom();
    }

    function closeChat() {
        panel.hidden = true;
        isOpen = false;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.classList.remove('is-active');
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
        div.id = 'pub-ai-typing';
        div.innerHTML = '<span class="ai-chat__msg-avatar">🤖</span><div class="ai-chat__msg-bubble"><span class="ai-chat__dots"><span>.</span><span>.</span><span>.</span></span></div>';
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
        }

        actionLabel.textContent = label;
        actionBar.hidden = false;
    }

    function hideActionBar() {
        pendingAction = null;
        actionBar.hidden = true;
    }

    async function sendMessage(text) {
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
                input.focus();
                return;
            }

            const data = await res.json();
            addMessage('bot', data.reply);
            history.push({ role: 'assistant', content: data.reply });

            if (history.length > 20) {
                history = history.slice(-16);
            }

            if (data.action) {
                // Auto-execute read-only queries
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
                addMessage('bot', data.result || '✅ Ação realizada com sucesso!');
            } else {
                addMessage('bot', '❌ Erro ao processar. Tente novamente.');
            }
        } catch {
            removeTypingIndicator();
            addMessage('bot', '❌ Erro de conexão.');
        }
    }

    // Event listeners
    toggle.addEventListener('click', () => isOpen ? closeChat() : openChat());
    closeBtn.addEventListener('click', closeChat);

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (text) sendMessage(text);
    });

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
})();
</script>
