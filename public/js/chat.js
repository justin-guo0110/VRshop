class ChatWidget {
    constructor() {
        this.isOpen = false;
        this.pollingInterval = null;
        this.unreadInterval = null;
        this.unreadCount = 0;
        // Use a relative path so it works even when the project folder contains spaces
        this.apiUrl = '../api/chat.php';

        this.createElements();
        this.bindEvents();
        this.startUnreadPolling();
    }

    createElements() {
        const container = document.createElement('div');
        container.className = 'chat-widget';
        container.innerHTML = `
            <div class="chat-window" id="chatWindow">
                <div class="chat-header">
                    <h3>客服支援</h3>
                    <button class="chat-close-btn" id="chatCloseBtn">✕</button>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div style="text-align:center;color:#999;font-size:12px;margin-top:20px;">
                        和我們開始對話吧！
                    </div>
                </div>
                <form class="chat-input-area" id="chatForm">
                    <input type="text" class="chat-input" id="chatInput" placeholder="請輸入訊息…" autocomplete="off">
                    <button type="submit" class="chat-send-btn">
                        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg>
                    </button>
                </form>
            </div>
            <button class="chat-toggle-btn" id="chatToggleBtn">
                <span class="chat-badge chat-badge-hidden" id="chatBadge"></span>
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
        this.badgeEl = document.getElementById('chatBadge');
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
            this.setUnreadCount(0);
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

    startUnreadPolling() {
        this.checkUnread();
        if (this.unreadInterval) clearInterval(this.unreadInterval);
        this.unreadInterval = setInterval(() => this.checkUnread(), 5000);
    }

    async checkUnread() {
        if (this.isOpen) return; // 開啟視窗時會自動把未讀歸零
        try {
            const res = await fetch(`${this.apiUrl}?action=get_unread_count`);
            const data = await res.json();
            if (data.success && typeof data.unread_count === 'number') {
                this.setUnreadCount(data.unread_count);
            }
        } catch (e) {
            console.error('Failed to check unread messages', e);
        }
    }

    setUnreadCount(count) {
        this.unreadCount = count;
        if (!this.badgeEl) return;
        if (count > 0) {
            this.badgeEl.textContent = count > 99 ? '99+' : count;
            this.badgeEl.classList.remove('chat-badge-hidden');
        } else {
            this.badgeEl.classList.add('chat-badge-hidden');
        }
    }

    async loadMessages() {
        try {
            const res = await fetch(`${this.apiUrl}?action=get_messages`);
            const data = await res.json();

            if (data.success && data.messages) {
                this.renderMessages(data.messages);
                this.setUnreadCount(0);
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
                alert('訊息送出失敗: ' + (data.error || '未知錯誤'));
            }
        } catch (e) {
            console.error('Send error', e);
            alert('網路連線錯誤，請稍後再試');
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
