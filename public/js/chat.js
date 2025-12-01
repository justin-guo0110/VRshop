class ChatWidget {
    constructor() {
        this.isOpen = false;
        this.pollingInterval = null;
        // Use a relative path so it works even when the project folder contains spaces
        this.apiUrl = '../api/chat.php';

        this.createElements();
        this.bindEvents();
    }

    createElements() {
        const container = document.createElement('div');
        container.className = 'chat-widget';
        container.innerHTML = `
            <div class="chat-window" id="chatWindow">
                <div class="chat-header">
                    <h3>Customer Support</h3>
                    <button class="chat-close-btn" id="chatCloseBtn">✕</button>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div style="text-align:center;color:#999;font-size:12px;margin-top:20px;">
                        Start a conversation with us!
                    </div>
                </div>
                <form class="chat-input-area" id="chatForm">
                    <input type="text" class="chat-input" id="chatInput" placeholder="Type a message..." autocomplete="off">
                    <button type="submit" class="chat-send-btn">
                        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg>
                    </button>
                </form>
            </div>
            <button class="chat-toggle-btn" id="chatToggleBtn">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"></path></svg>
            </button>
        `;
        document.body.appendChild(container);

        this.windowEl = document.getElementById('chatWindow');
        this.messagesEl = document.getElementById('chatMessages');
        this.inputEl = document.getElementById('chatInput');
        this.toggleBtn = document.getElementById('chatToggleBtn');
        this.closeBtn = document.getElementById('chatCloseBtn');
        this.formEl = document.getElementById('chatForm');
    }

    bindEvents() {
        this.toggleBtn.addEventListener('click', () => this.toggle());
        this.closeBtn.addEventListener('click', () => this.toggle());
        this.formEl.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
    }

    toggle() {
        this.isOpen = !this.isOpen;
        this.windowEl.classList.toggle('open', this.isOpen);

        if (this.isOpen) {
            this.loadMessages();
            this.startPolling();
            setTimeout(() => this.inputEl.focus(), 300);
        } else {
            this.stopPolling();
        }
    }

    startPolling() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        this.pollingInterval = setInterval(() => this.loadMessages(), 3000);
    }

    stopPolling() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        this.pollingInterval = null;
    }

    async loadMessages() {
        try {
            const res = await fetch(`${this.apiUrl}?action=get_messages`);
            const data = await res.json();

            if (data.success && data.messages) {
                this.renderMessages(data.messages);
            }
        } catch (e) {
            console.error('Failed to load messages', e);
        }
    }

    renderMessages(messages) {
        // Simple diffing: only update if length changed or last message different
        // For now, just re-render to be safe and simple
        const wasAtBottom = this.messagesEl.scrollTop + this.messagesEl.clientHeight >= this.messagesEl.scrollHeight - 10;

        this.messagesEl.innerHTML = messages.map(m => `
            <div class="message ${m.sender === 'user' ? 'user' : 'admin'}">
                ${this.escapeHtml(m.message)}
                <!-- <span class="message-time">${new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span> -->
            </div>
        `).join('');

        if (wasAtBottom || messages.length === 1) { // Auto scroll on new load or first message
            this.messagesEl.scrollTop = this.messagesEl.scrollHeight;
        }
    }

    async sendMessage() {
        const text = this.inputEl.value.trim();
        if (!text) return;

        // Optimistic UI
        const tempMsg = { sender: 'user', message: text, created_at: new Date().toISOString() };
        // this.renderMessages([...currentMessages, tempMsg]); // Complex without state management, skip for now

        this.inputEl.value = '';

        try {
            const formData = new URLSearchParams();
            formData.append('message', text);

            const res = await fetch(`${this.apiUrl}?action=send_message`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                this.loadMessages();
            } else {
                alert('Failed to send message: ' + (data.error || 'Unknown error'));
            }
        } catch (e) {
            console.error('Send error', e);
            alert('Network error');
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ChatWidget();
});
