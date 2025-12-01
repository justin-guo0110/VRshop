class AdminChat {
    constructor() {
        this.currentChatId = null;
        this.currentChatIsUser = false;
        this.pollingInterval = null;
        this.apiUrl = '../api/admin.php';

        this.listEl = document.getElementById('adminChatList');
        this.messagesEl = document.getElementById('adminChatMessages');
        this.titleEl = document.getElementById('chatTitle');
        this.inputEl = document.getElementById('adminChatInput');
        this.sendBtn = document.getElementById('adminChatSendBtn');

        if (this.listEl) {
            this.init();
        }
    }

    init() {
        this.loadChatList();
        setInterval(() => this.loadChatList(), 10000); // Refresh list every 10s

        this.sendBtn.addEventListener('click', () => this.sendReply());
        this.inputEl.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendReply();
        });
    }

    async loadChatList() {
        try {
            const res = await api.get(`${this.apiUrl}?action=get_all_chats`);
            if (res.success && res.chats) {
                this.renderList(res.chats);
            }
        } catch (e) {
            console.error('Failed to load chat list', e);
        }
    }

    renderList(chats) {
        this.listEl.innerHTML = chats.map(c => {
            const isUser = c.chat_id.length < 10; // Simple heuristic based on ID length
            const displayName = isUser ? `User #${c.chat_id}` : 'Guest';
            const isActive = this.currentChatId === c.chat_id ? 'background:#f0f7ff;' : '';

            return `
                <div style="padding:15px; border-bottom:1px solid #eee; cursor:pointer; ${isActive}" 
                     onclick="window.adminChat.openChat('${c.chat_id}', ${isUser})">
                    <div style="font-weight:600; color:#333;">${displayName}</div>
                    <div style="font-size:12px; color:#666; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        ${this.escapeHtml(c.last_message || '')}
                    </div>
                    <div style="font-size:10px; color:#999; margin-top:4px;">
                        ${new Date(c.last_activity).toLocaleString()}
                    </div>
                </div>
            `;
        }).join('');
    }

    openChat(chatId, isUser) {
        this.currentChatId = chatId;
        this.currentChatIsUser = isUser;
        this.titleEl.textContent = isUser ? `Chat with User #${chatId}` : 'Chat with Guest';

        this.loadMessages();
        this.startPolling();
        this.inputEl.focus();
    }

    startPolling() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        this.pollingInterval = setInterval(() => this.loadMessages(), 3000);
    }

    async loadMessages() {
        if (!this.currentChatId) return;
        try {
            const res = await api.get(`${this.apiUrl}?action=get_chat_history&chat_id=${this.currentChatId}`);
            if (res.success && res.messages) {
                this.renderMessages(res.messages);
            }
        } catch (e) {
            console.error('Failed to load messages', e);
        }
    }

    renderMessages(messages) {
        const wasAtBottom = this.messagesEl.scrollTop + this.messagesEl.clientHeight >= this.messagesEl.scrollHeight - 10;

        this.messagesEl.innerHTML = messages.map(m => `
            <div style="margin-bottom:10px; display:flex; justify-content:${m.sender === 'admin' ? 'flex-end' : 'flex-start'}">
                <div style="
                    max-width:70%; 
                    padding:8px 12px; 
                    border-radius:12px; 
                    background:${m.sender === 'admin' ? '#667eea' : '#f1f1f1'}; 
                    color:${m.sender === 'admin' ? 'white' : '#333'};
                    font-size:14px;
                ">
                    ${this.escapeHtml(m.message)}
                </div>
            </div>
        `).join('');

        if (wasAtBottom || messages.length === 1) {
            this.messagesEl.scrollTop = this.messagesEl.scrollHeight;
        }
    }

    async sendReply() {
        const msg = this.inputEl.value.trim();
        if (!msg || !this.currentChatId) return;

        const payload = { message: msg };
        if (this.currentChatIsUser) payload.user_id = this.currentChatId;
        else payload.session_id = this.currentChatId;

        try {
            const res = await api.post(`${this.apiUrl}?action=reply_chat`, payload);
            if (res.success) {
                this.inputEl.value = '';
                this.loadMessages();
            }
        } catch (e) {
            console.error('Failed to send reply', e);
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Expose to window for onclick handlers in HTML string
window.adminChat = new AdminChat();
