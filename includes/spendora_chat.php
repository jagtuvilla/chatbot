<?php
if (!isset($_SESSION['user_id'])) {
    return;
}
?>

<!-- Spendora Chat Head -->
<div id="spendora-chat-head" class="fixed bottom-4 right-4 z-50">
    <button onclick="toggleChat()" class="bg-gradient-to-br from-[#187C19] to-[#0F4D10] hover:from-[#0F4D10] hover:to-[#187C19] text-white rounded-full p-2 shadow-lg transition-all duration-300">
        <div class="w-10 h-10 flex items-center justify-center">
            <svg viewBox="0 0 100 100" class="w-full h-full">
                <!-- Background glow -->
                <defs>
                    <radialGradient id="glow" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                        <stop offset="0%" style="stop-color:#187C19;stop-opacity:0.3"/>
                        <stop offset="100%" style="stop-color:#187C19;stop-opacity:0"/>
                    </radialGradient>
                </defs>
                <!-- Glow effect -->
                <circle cx="50" cy="50" r="48" fill="url(#glow)"/>
                <!-- Face circle with gradient -->
                <circle cx="50" cy="50" r="45" fill="white" class="shadow-lg"/>
                <!-- $ Eyes with shadow -->
                <g filter="drop-shadow(0 2px 2px rgba(0,0,0,0.1))">
                    <text x="28" y="48" font-family="Arial" font-size="26" font-weight="bold" fill="#187C19" transform="rotate(-5,28,48)">$</text>
                    <text x="62" y="48" font-family="Arial" font-size="26" font-weight="bold" fill="#187C19" transform="rotate(5,62,48)">$</text>
                </g>
                <!-- Smile with gradient -->
                <path d="M32 62 Q50 80 68 62" stroke="#187C19" stroke-width="4" fill="none" stroke-linecap="round" class="animate-pulse"/>
                <!-- Sparkle effects -->
                <circle cx="25" cy="30" r="2" fill="#187C19" opacity="0.6"/>
                <circle cx="75" cy="30" r="2" fill="#187C19" opacity="0.6"/>
            </svg>
        </div>
    </button>
</div>

<!-- Spendora Chat Window -->
<div id="spendora-chat-window" class="fixed bottom-4 right-4 w-96 h-[600px] bg-white dark:bg-dark-bg-secondary rounded-lg shadow-xl z-50 hidden flex flex-col">
    <div class="chat-head bg-gradient-to-r from-[#187C19] to-[#0F4D10] text-white p-4 rounded-t-lg flex items-center justify-between cursor-pointer" onclick="toggleChat()">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center overflow-hidden shadow-lg">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                    <!-- Background glow -->
                    <defs>
                        <radialGradient id="glow2" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                            <stop offset="0%" style="stop-color:#187C19;stop-opacity:0.3"/>
                            <stop offset="100%" style="stop-color:#187C19;stop-opacity:0"/>
                        </radialGradient>
                    </defs>
                    <!-- Glow effect -->
                    <circle cx="50" cy="50" r="48" fill="url(#glow2)"/>
                    <!-- Face circle with gradient -->
                    <circle cx="50" cy="50" r="45" fill="white" class="shadow-lg"/>
                    <!-- $ Eyes with shadow -->
                    <g filter="drop-shadow(0 2px 2px rgba(0,0,0,0.1))">
                        <text x="28" y="48" font-family="Arial" font-size="26" font-weight="bold" fill="#187C19" transform="rotate(-5,28,48)">$</text>
                        <text x="62" y="48" font-family="Arial" font-size="26" font-weight="bold" fill="#187C19" transform="rotate(5,62,48)">$</text>
                    </g>
                    <!-- Smile with gradient -->
                    <path d="M32 62 Q50 80 68 62" stroke="#187C19" stroke-width="4" fill="none" stroke-linecap="round" class="animate-pulse"/>
                    <!-- Sparkle effects -->
                    <circle cx="25" cy="30" r="2" fill="#187C19" opacity="0.6"/>
                    <circle cx="75" cy="30" r="2" fill="#187C19" opacity="0.6"/>
                </svg>
            </div>
            <div>
                <h3 class="font-bold">Spendora</h3>
                <p class="text-sm text-gray-200">Your Financial Assistant</p>
            </div>
        </div>
        <button onclick="toggleChat()" class="text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-4">
        <!-- Messages will be inserted here -->
    </div>
    <div class="border-t dark:border-gray-700 p-4">
        <form id="chat-form" class="flex space-x-2" onsubmit="return handleChatSubmit(event)">
            <div class="relative flex-1">
                <input type="text" id="chat-input" class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500" placeholder="Type your message...">
                <button type="button" onclick="toggleQuickQuestions()" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </button>
                <div id="quick-questions" class="absolute bottom-full right-0 mb-2 w-64 bg-white dark:bg-dark-bg-secondary rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 hidden">
                    <div class="p-2">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Quick Questions</h4>
                        <div class="space-y-2">
                            <button type="button" onclick="askQuestion('How can I create a budget?')" class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                How can I create a budget?
                            </button>
                            <button type="button" onclick="askQuestion('What are some saving tips?')" class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                What are some saving tips?
                            </button>
                            <button type="button" onclick="askQuestion('How do I track my expenses?')" class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                How do I track my expenses?
                            </button>
                            <button type="button" onclick="askQuestion('What are good investment strategies?')" class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                What are good investment strategies?
                            </button>
                            <button type="button" onclick="askQuestion('How can I manage my debt?')" class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                How can I manage my debt?
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
// Store chat state in localStorage
let chatState = {
    isOpen: false,
    lastMessage: null
};

// Load chat state from localStorage
function loadChatState() {
    const savedState = localStorage.getItem('spendoraChatState');
    if (savedState) {
        chatState = JSON.parse(savedState);
        if (chatState.isOpen) {
            toggleChat();
        }
    }
}

// Save chat state to localStorage
function saveChatState() {
    localStorage.setItem('spendoraChatState', JSON.stringify(chatState));
}

function toggleChat() {
    const chatHead = document.getElementById('spendora-chat-head');
    const chatWindow = document.getElementById('spendora-chat-window');
    
    if (chatWindow.classList.contains('hidden')) {
        chatHead.classList.add('hidden');
        chatWindow.classList.remove('hidden');
        loadChatHistory();
        chatState.isOpen = true;
    } else {
        chatHead.classList.remove('hidden');
        chatWindow.classList.add('hidden');
        chatState.isOpen = false;
    }
    saveChatState();
}

function toggleQuickQuestions() {
    const quickQuestions = document.getElementById('quick-questions');
    quickQuestions.classList.toggle('hidden');
}

function askQuestion(question) {
    const input = document.getElementById('chat-input');
    input.value = question;
    handleChatSubmit(new Event('submit'));
    document.getElementById('quick-questions').classList.add('hidden');
}

function handleChatSubmit(event) {
    event.preventDefault();
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (message) {
        // Add user message to chat
        const chatMessages = document.getElementById('chat-messages');
        const userMessageDiv = document.createElement('div');
        userMessageDiv.className = 'flex justify-end';
        userMessageDiv.innerHTML = `
            <div class="bg-blue-600 text-white rounded-lg px-4 py-2 max-w-[80%]">
                ${message}
            </div>
        `;
        chatMessages.appendChild(userMessageDiv);
        
        // Send message to server
        fetch('spendora.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Add bot response to chat
            const botMessageDiv = document.createElement('div');
            botMessageDiv.className = 'flex justify-start';
            botMessageDiv.innerHTML = `
                <div class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg px-4 py-2 max-w-[80%]">
                    ${data.response}
                </div>
            `;
            chatMessages.appendChild(botMessageDiv);
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Save last message
            chatState.lastMessage = message;
            saveChatState();
        })
        .catch(error => {
            console.error('Error sending message:', error);
            const botMessageDiv = document.createElement('div');
            botMessageDiv.className = 'flex justify-start';
            botMessageDiv.innerHTML = `
                <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg px-4 py-2 max-w-[80%]">
                    Failed to send message. Please try again.
                </div>
            `;
            chatMessages.appendChild(botMessageDiv);
        });
        
        input.value = '';
    }
    return false;
}

function loadChatHistory() {
    fetch('spendora.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ get_history: true })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.innerHTML = '';
        
        data.history.forEach(msg => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex ${msg.is_user ? 'justify-end' : 'justify-start'}`;
            messageDiv.innerHTML = `
                <div class="${msg.is_user ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'} rounded-lg px-4 py-2 max-w-[80%]">
                    ${msg.message}
                </div>
            `;
            chatMessages.appendChild(messageDiv);
        });
        
        chatMessages.scrollTop = chatMessages.scrollHeight;
    })
    .catch(error => {
        console.error('Error loading chat history:', error);
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.innerHTML = `
            <div class="flex justify-center">
                <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg px-4 py-2">
                    Failed to load chat history. Please try again.
                </div>
            </div>
        `;
    });
}

// Close quick questions when clicking outside
document.addEventListener('click', function(event) {
    const quickQuestions = document.getElementById('quick-questions');
    const lightBulb = event.target.closest('button[onclick="toggleQuickQuestions()"]');
    
    if (!quickQuestions.contains(event.target) && !lightBulb) {
        quickQuestions.classList.add('hidden');
    }
});

// Load chat state when the page loads
document.addEventListener('DOMContentLoaded', loadChatState);
</script> 