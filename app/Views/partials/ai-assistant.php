<?php
/**
 * AI Assistant floating chatbot widget.
 * Included in the vendor layout — appears as a floating button on all vendor pages.
 */
?>
<div id="ai-assistant" class="ai-chat" aria-label="Assistente IA">
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
                    <strong>Assistente IA</strong>
                    <span class="ai-chat__status">Online</span>
                </div>
            </div>
            <button type="button" class="ai-chat__close" id="ai-close" aria-label="Fechar">✕</button>
        </div>

        <div class="ai-chat__messages" id="ai-messages">
            <div class="ai-chat__msg ai-chat__msg--bot">
                <span class="ai-chat__msg-avatar">🤖</span>
                <div class="ai-chat__msg-bubble">
                    Olá! 👋 Sou a assistente IA do Apprumo — seu funcionário virtual! Posso agendar atendimentos, registrar vendas de produtos, criar serviços e muito mais. Tudo com sua confirmação antes de executar. Como posso ajudar?
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
            <input type="text" class="ai-chat__input" id="ai-input" placeholder="Digite sua mensagem..." maxlength="500" aria-label="Mensagem">
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

        if (type === 'create_service') {
            label = '📋 Criar serviço "' + (data.title || '?') + '"?';
        } else if (type === 'create_product') {
            label = '📦 Criar produto "' + (data.name || '?') + '"?';
        } else if (type === 'create_appointment') {
            const date = data.appointment_date || '';
            const time = data.start_time || '';
            label = '📅 Agendar "' + (data.customer_name || '?') + '" em ' + date + ' às ' + time + '?';
        } else if (type === 'sell_product') {
            label = '🛒 Registrar venda de ' + (data.quantity || 1) + 'x produto ID ' + (data.product_id || '?') + '?';
        } else if (type === 'update_appointment_status') {
            const statusLabels = {confirmed: 'Confirmado', completed: 'Concluído', cancelled: 'Cancelado', no_show: 'Não compareceu'};
            label = '🔄 Alterar agendamento #' + (data.appointment_id || '?') + ' para "' + (statusLabels[data.status] || data.status || '?') + '"?';
        } else if (type === 'delete_appointment') {
            label = '🗑️ Excluir agendamento #' + (data.appointment_id || '?') + '?';
        } else if (type === 'create_waiting_entry') {
            label = '⏳ Adicionar "' + (data.customer_name || '?') + '" na fila de espera?';
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

            // Keep history manageable
            if (history.length > 20) {
                history = history.slice(-16);
            }

            // Show action confirmation if AI returned an action
            if (data.action) {
                showActionBar(data.action);
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

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (text) sendMessage(text);
    });

    actionConfirm.addEventListener('click', confirmAction);
    actionCancel.addEventListener('click', () => {
        hideActionBar();
        addMessage('bot', '🚫 Ação cancelada.');
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) closeChat();
    });
})();
</script>
