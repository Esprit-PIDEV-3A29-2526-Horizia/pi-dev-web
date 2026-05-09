async function sendChatMessage() {
    const input = document.getElementById('chatbot-input');
    const messages = document.getElementById('chatbot-messages');

    const message = input.value.trim();

    if (message === '') {
        return;
    }

    messages.innerHTML += `
        <div class="chat-message user-message">
            ${escapeHtml(message)}
        </div>
    `;

    input.value = '';

    messages.innerHTML += `
        <div class="chat-message bot-message chatbot-loading">
            Horozia réfléchit...
        </div>
    `;

    messages.scrollTop = messages.scrollHeight;

    try {
        const response = await fetch('/chatbot/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        });

        const data = await response.json();

        const loading = document.querySelector('.chatbot-loading');

        if (loading) {
            loading.remove();
        }

        if (data.success) {
            messages.innerHTML += `
                <div class="chat-message bot-message">
                    <div class="ai-response">
                        ${formatText(data.reply)}
                    </div>
                    <div>
                        ${data.reply_html ?? ''}
                    </div>
                </div>
            `;
        } else {
            messages.innerHTML += `
                <div class="chat-message bot-message">
                    ${escapeHtml(data.reply)}
                </div>
            `;
        }

        messages.scrollTop = messages.scrollHeight;

    } catch (error) {
        const loading = document.querySelector('.chatbot-loading');

        if (loading) {
            loading.remove();
        }

        messages.innerHTML += `
            <div class="chat-message bot-message">
                Une erreur est survenue. Veuillez réessayer.
            </div>
        `;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}

function formatText(text) {
    let safe = escapeHtml(text);

    safe = safe
        .replace(/\\/g, '')
        .replace(/🇫🇷 Programme de ([^:]+) :/g, '<div class="ai-program-header">🇫🇷 <span>Programme de $1</span></div>')
        .replace(/🇮🇹 Programme de ([^:]+) :/g, '<div class="ai-program-header">🇮🇹 <span>Programme de $1</span></div>')
        .replace(/Jour 1\s*:/g, '<div class="ai-day-card"><div class="ai-day-title">Jour 1</div><ul>')
        .replace(/Jour 2\s*:/g, '</ul></div><div class="ai-day-card"><div class="ai-day-title">Jour 2</div><ul>')
        .replace(/Jour 3\s*:/g, '</ul></div><div class="ai-day-card"><div class="ai-day-title">Jour 3</div><ul>')
        .replace(/Jour 4\s*:/g, '</ul></div><div class="ai-day-card"><div class="ai-day-title">Jour 4</div><ul>')
        .replace(/Jour 5\s*:/g, '</ul></div><div class="ai-day-card"><div class="ai-day-title">Jour 5</div><ul>')
        .replace(/Conseils pratiques\s*:/g, '</ul></div><div class="ai-tips-card"><div class="ai-tips-title">💡 Conseils pratiques</div><ul>')
        .replace(/Bon voyage avec Horozia ✈️/g, '</ul></div><div class="ai-final-card">Bon voyage avec Horozia ✈️</div>')
        .replace(/\n- (.*?)(?=\n|$)/g, '<li>$1</li>')
        .replace(/\n/g, '');

    if (!safe.includes('ai-final-card')) {
        safe += '</ul></div>';
    }

    return <div class="ai-premium-response">${safe}</div>;
}