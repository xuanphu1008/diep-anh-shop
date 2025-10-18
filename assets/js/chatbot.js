// assets/js/chatbot.js - Chatbot tư vấn

let chatbotOpen = false;

// Toggle chatbot window
function toggleChatbot() {
    const chatbot = document.getElementById('chatbot-window');
    
    if (!chatbot) {
        createChatbotWindow();
    } else {
        chatbotOpen = !chatbotOpen;
        chatbot.classList.toggle('active');
    }
}

// Tạo cửa sổ chatbot
function createChatbotWindow() {
    const chatbotHTML = `
        <div id="chatbot-window" class="chatbot-window active">
            <div class="chatbot-header">
                <div>
                    <i class="fas fa-robot"></i>
                    <strong>Trợ lý Diệp Anh</strong>
                </div>
                <span class="chatbot-close" onclick="toggleChatbot()">
                    <i class="fas fa-times"></i>
                </span>
            </div>
            <div class="chatbot-body" id="chatbot-messages">
                <div class="chat-message bot">
                    <div class="message-bubble">
                        Xin chào! Tôi là trợ lý ảo của Diệp Anh Computer. Tôi có thể giúp gì cho bạn?
                    </div>
                </div>
            </div>
            <div class="chatbot-footer">
                <div class="chatbot-input">
                    <input type="text" id="chatbot-input" placeholder="Nhập câu hỏi..." 
                           onkeypress="handleChatbotKeypress(event)">
                    <button onclick="sendChatMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', chatbotHTML);
    chatbotOpen = true;
    
    // Focus vào input
    setTimeout(() => {
        document.getElementById('chatbot-input').focus();
    }, 100);
}

// Xử lý Enter key
function handleChatbotKeypress(event) {
    if (event.key === 'Enter') {
        sendChatMessage();
    }
}

// Gửi tin nhắn
function sendChatMessage() {
    const input = document.getElementById('chatbot-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Hiển thị tin nhắn người dùng
    addChatMessage('user', message);
    input.value = '';
    
    // Hiển thị typing indicator
    addTypingIndicator();
    
    // Gửi request đến API
    fetch('api/chatbot.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        removeTypingIndicator();
        
        if (data.success) {
            // Hiển thị response
            addChatMessage('bot', data.message);
            
            // Hiển thị sản phẩm gợi ý nếu có
            if (data.products && data.products.length > 0) {
                addProductSuggestions(data.products);
            }
        } else {
            addChatMessage('bot', 'Xin lỗi, tôi không thể xử lý yêu cầu của bạn lúc này.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        removeTypingIndicator();
        addChatMessage('bot', 'Có lỗi xảy ra. Vui lòng thử lại sau!');
    });
}

// Thêm tin nhắn vào chat
function addChatMessage(sender, message) {
    const messagesContainer = document.getElementById('chatbot-messages');
    
    const messageHTML = `
        <div class="chat-message ${sender}">
            <div class="message-bubble">
                ${message.replace(/\n/g, '<br>')}
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
    scrollChatToBottom();
}

// Thêm typing indicator
function addTypingIndicator() {
    const messagesContainer = document.getElementById('chatbot-messages');
    
    const typingHTML = `
        <div class="chat-message bot typing-indicator" id="typing-indicator">
            <div class="message-bubble">
                <span>●</span>
                <span>●</span>
                <span>●</span>
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', typingHTML);
    scrollChatToBottom();
    
    // Thêm animation CSS nếu chưa có
    if (!document.querySelector('#typing-animation')) {
        const style = document.createElement('style');
        style.id = 'typing-animation';
        style.textContent = `
            .typing-indicator .message-bubble span {
                animation: typing 1.4s infinite;
                opacity: 0.3;
            }
            .typing-indicator .message-bubble span:nth-child(1) {
                animation-delay: 0s;
            }
            .typing-indicator .message-bubble span:nth-child(2) {
                animation-delay: 0.2s;
            }
            .typing-indicator .message-bubble span:nth-child(3) {
                animation-delay: 0.4s;
            }
            @keyframes typing {
                0%, 60%, 100% {
                    opacity: 0.3;
                }
                30% {
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

// Xóa typing indicator
function removeTypingIndicator() {
    const indicator = document.getElementById('typing-indicator');
    if (indicator) {
        indicator.remove();
    }
}

// Thêm gợi ý sản phẩm
function addProductSuggestions(products) {
    const messagesContainer = document.getElementById('chatbot-messages');
    
    let productsHTML = '<div class="product-suggestions">';
    
    products.forEach(product => {
        productsHTML += `
            <a href="${product.url}" class="product-suggestion-item" target="_blank">
                <img src="${product.image}" alt="${product.name}">
                <div class="product-suggestion-info">
                    <h4>${product.name}</h4>
                    <p class="product-suggestion-price">${product.price}</p>
                </div>
            </a>
        `;
    });
    
    productsHTML += '</div>';
    
    const suggestionMessage = `
        <div class="chat-message bot">
            <div class="message-bubble">
                ${productsHTML}
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', suggestionMessage);
    scrollChatToBottom();
    
    // Thêm CSS cho product suggestions
    if (!document.querySelector('#product-suggestions-styles')) {
        const style = document.createElement('style');
        style.id = 'product-suggestions-styles';
        style.textContent = `
            .product-suggestions {
                display: flex;
                flex-direction: column;
                gap: 10px;
                margin-top: 10px;
            }
            .product-suggestion-item {
                display: flex;
                gap: 10px;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 5px;
                transition: all 0.3s;
            }
            .product-suggestion-item:hover {
                background: #e9ecef;
                transform: translateX(5px);
            }
            .product-suggestion-item img {
                width: 60px;
                height: 60px;
                object-fit: cover;
                border-radius: 5px;
            }
            .product-suggestion-info {
                flex: 1;
            }
            .product-suggestion-info h4 {
                font-size: 14px;
                margin: 0 0 5px 0;
                color: #333;
            }
            .product-suggestion-price {
                color: #dc3545;
                font-weight: bold;
                margin: 0;
            }
        `;
        document.head.appendChild(style);
    }
}

// Scroll xuống cuối chat
function scrollChatToBottom() {
    const messagesContainer = document.getElementById('chatbot-messages');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Gợi ý câu hỏi nhanh
function addQuickReplies() {
    const quickReplies = [
        'Laptop gaming giá rẻ',
        'Laptop văn phòng',
        'PC gaming',
        'Khuyến mãi',
        'Chính sách bảo hành'
    ];
    
    const messagesContainer = document.getElementById('chatbot-messages');
    
    let repliesHTML = '<div class="quick-replies">';
    quickReplies.forEach(reply => {
        repliesHTML += `
            <button class="quick-reply-btn" onclick="sendQuickReply('${reply}')">
                ${reply}
            </button>
        `;
    });
    repliesHTML += '</div>';
    
    const quickReplyMessage = `
        <div class="chat-message bot">
            <div class="message-bubble">
                Hoặc bạn có thể chọn:
                ${repliesHTML}
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', quickReplyMessage);
    scrollChatToBottom();
    
    // Thêm CSS
    if (!document.querySelector('#quick-replies-styles')) {
        const style = document.createElement('style');
        style.id = 'quick-replies-styles';
        style.textContent = `
            .quick-replies {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                margin-top: 10px;
            }
            .quick-reply-btn {
                padding: 5px 12px;
                background: #fff;
                border: 1px solid #0066cc;
                color: #0066cc;
                border-radius: 15px;
                cursor: pointer;
                font-size: 12px;
                transition: all 0.3s;
            }
            .quick-reply-btn:hover {
                background: #0066cc;
                color: #fff;
            }
        `;
        document.head.appendChild(style);
    }
}

// Gửi quick reply
function sendQuickReply(message) {
    document.getElementById('chatbot-input').value = message;
    sendChatMessage();
}

// Khởi tạo chatbot khi trang load
document.addEventListener('DOMContentLoaded', function() {
    // Tự động mở chatbot sau 5 giây (tùy chọn)
    // setTimeout(() => {
    //     if (!chatbotOpen) {
    //         toggleChatbot();
    //         addQuickReplies();
    //     }
    // }, 5000);
});