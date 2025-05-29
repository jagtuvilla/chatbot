class SpendoraBot {
    constructor(userName) {
        this.userName = userName;
        this.isVisible = false;
        this.isMinimized = false;
        this.isFullscreen = false;
        this.lastCategory = null; // Track the last advice category
        this.welcomeSent = false;
        this.isQuickQuestionsVisible = false;
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Chat head click event
        document.getElementById('spendora-chat-head').addEventListener('click', () => {
            this.showChat();
        });

        // Enter key event for input field
        document.getElementById('user-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });

        // Send button click event
        document.querySelector('.send-button').addEventListener('click', () => {
            this.sendMessage();
        });

        // Minimize button click event
        document.getElementById('minimize-btn').addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event from bubbling to header
            this.hideChat();
        });

        // Toggle chat visibility and fullscreen
        document.querySelector('.chat-header').addEventListener('click', (e) => {
            // Don't toggle if clicking the minimize button
            if (e.target.id === 'minimize-btn') {
                this.toggleChat();
            } else {
                this.toggleFullscreen();
            }
        });

        // Quick Questions toggle
        document.getElementById('quick-questions-toggle').addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleQuickQuestions();
        });

        // Quick Questions click handlers
        document.querySelectorAll('.quick-question').forEach(button => {
            button.addEventListener('click', () => {
                const question = button.getAttribute('data-question');
                this.handleQuickQuestion(question);
            });
        });

        // Close Quick Questions panel when clicking outside
        document.addEventListener('click', (e) => {
            const panel = document.getElementById('quick-questions-panel');
            const toggle = document.getElementById('quick-questions-toggle');
            if (!panel.contains(e.target) && !toggle.contains(e.target)) {
                this.hideQuickQuestions();
            }
        });
    }

    toggleFullscreen() {
        const chatContainer = document.querySelector('.chat-container');
        const chatHeader = document.querySelector('.chat-header');
        const chatBody = document.getElementById('chat-body');
        
        this.isFullscreen = !this.isFullscreen;
        
        if (this.isFullscreen) {
            chatContainer.classList.add('fullscreen');
            chatHeader.classList.add('fullscreen');
            chatBody.classList.add('fullscreen');
            // Ensure chat is not minimized when going fullscreen
            if (this.isMinimized) {
                this.toggleChat();
            }
        } else {
            chatContainer.classList.remove('fullscreen');
            chatHeader.classList.remove('fullscreen');
            chatBody.classList.remove('fullscreen');
        }
        
        // Scroll to bottom after transition
        setTimeout(() => {
            const messagesDiv = document.getElementById('chat-messages');
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }, 300);
    }

    toggleChat() {
        const chatBody = document.getElementById('chat-body');
        const minimizeBtn = document.getElementById('minimize-btn');
        this.isMinimized = !this.isMinimized;
        
        if (this.isMinimized) {
            chatBody.style.maxHeight = '0';
            chatBody.style.opacity = '0';
            minimizeBtn.textContent = '+';
            // Exit fullscreen if minimizing
            if (this.isFullscreen) {
                this.toggleFullscreen();
            }
        } else {
            chatBody.style.maxHeight = this.isFullscreen ? '100vh' : '400px';
            chatBody.style.opacity = '1';
            minimizeBtn.textContent = '−';
        }
    }

    showChat() {
        const chatContainer = document.getElementById('spendora-chat');
        chatContainer.classList.add('visible');
        document.getElementById('user-input').focus();
        
        // Send welcome message if this is the first time opening
        if (!this.welcomeSent) {
            this.sendWelcomeMessage();
            this.welcomeSent = true;
        }
    }

    hideChat() {
        const chatContainer = document.getElementById('spendora-chat');
        chatContainer.classList.remove('visible');
    }

    addMessage(text, isBot = false) {
        const messagesDiv = document.getElementById('chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `max-w-[80%] ${isBot ? 'ml-0 mr-auto bg-blue-100 dark:bg-blue-900' : 'ml-auto mr-0 bg-[#187C19] text-white'} rounded-lg p-3 break-words`;
        messageDiv.textContent = text;
        messagesDiv.appendChild(messageDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    getRandomTip(category) {
        const tips = financialDatasets[category];
        return tips[Math.floor(Math.random() * tips.length)];
    }

    handleFollowUp(input) {
        const inputLower = input.toLowerCase();
        if (!this.lastCategory) return false;

        if (inputLower.includes('yes') || inputLower.includes('sure') || inputLower.includes('okay') || inputLower.includes('yeah')) {
            let tip = '';
            let followUp = '';
            switch (this.lastCategory) {
                case 'budgetingTips':
                    tip = `Here's another budgeting tip: ${this.getRandomTip('budgetingTips')}`;
                    followUp = 'Would you like another budgeting tip?';
                    break;
                case 'investmentAdvice':
                    tip = `Here's more investment advice: ${this.getRandomTip('investmentAdvice')}`;
                    followUp = 'Would you like another investment tip?';
                    break;
                case 'savingStrategies':
                    tip = `Here's another saving strategy: ${this.getRandomTip('savingStrategies')}`;
                    followUp = 'Would you like another saving tip?';
                    break;
                case 'debtManagement':
                    tip = `Here's another debt management tip: ${this.getRandomTip('debtManagement')}`;
                    followUp = 'Would you like another tip about managing debt?';
                    break;
            }
            this.addMessage(tip, true);
            setTimeout(() => {
                this.addMessage(followUp, true);
            }, 500);
            return true;
        } else if (inputLower.includes('no') || inputLower.includes('nope') || inputLower.includes('thanks') || inputLower.includes('thank')) {
            this.lastCategory = null;
            this.addMessage(`Is there anything else you'd like to know about? I can help you with budgeting, investing, saving money, or managing debt.`, true);
            return true;
        }
        return false;
    }

    generateResponse(input) {
        const inputLower = input.toLowerCase();
        
        // First check if this is a follow-up response
        if (this.handleFollowUp(input)) {
            return null; // Response already handled
        }

        // Check for system-related questions first
        const systemResponse = this.handleSystemQuestion(inputLower);
        if (systemResponse) {
            return systemResponse;
        }

        // Check for basic greetings
        if (inputLower.includes('hello') || inputLower.includes('hi')) {
            this.lastCategory = null;
            return `Hello ${this.userName}! How can I help you today?`;
        }
        else if (inputLower.includes('how are you') || inputLower.includes('how\'s your day')) {
            this.lastCategory = null;
            return `I'm doing great! Thank you for asking. How can I assist you with your finances today?`;
        }

        // Check for system-related keywords
        if (inputLower.includes('how to') || inputLower.includes('how do i') || 
            inputLower.includes('show me') || inputLower.includes('help me with') || 
            inputLower.includes('guide') || inputLower.includes('explain')) {
            
            // Transaction related
            if (inputLower.includes('transaction') || inputLower.includes('add') || 
                inputLower.includes('record') || inputLower.includes('enter')) {
                return this.handleSystemQuestion('add transaction');
            }
            
            // Account related
            if (inputLower.includes('account') || inputLower.includes('wallet')) {
                return this.handleSystemQuestion('add account');
            }
            
            // Budget related
            if (inputLower.includes('budget') || inputLower.includes('limit') || 
                inputLower.includes('spending')) {
                return this.handleSystemQuestion('create budget');
            }
            
            // Report related
            if (inputLower.includes('report') || inputLower.includes('chart') || 
                inputLower.includes('analysis') || inputLower.includes('view') || 
                inputLower.includes('see')) {
                return this.handleSystemQuestion('report');
            }
            
            // Category related
            if (inputLower.includes('category') || inputLower.includes('categorize') || 
                inputLower.includes('organize')) {
                return this.handleSystemQuestion('category');
            }
            
            // Navigation related
            if (inputLower.includes('find') || inputLower.includes('where') || 
                inputLower.includes('menu') || inputLower.includes('section')) {
                return this.handleSystemQuestion('find');
            }
        }

        // Check for financial advice topics
        if (inputLower.includes('budget') || inputLower.includes('spending')) {
            this.lastCategory = 'budgetingTips';
            const tip = `Here's a budgeting tip: ${this.getRandomTip('budgetingTips')}`;
            this.addMessage(tip, true);
            setTimeout(() => {
                this.addMessage('Would you like another budgeting tip?', true);
            }, 500);
            return null;
        }
        else if (inputLower.includes('invest') || inputLower.includes('stock')) {
            this.lastCategory = 'investmentAdvice';
            const tip = `Regarding investments: ${this.getRandomTip('investmentAdvice')}`;
            this.addMessage(tip, true);
            setTimeout(() => {
                this.addMessage('Would you like another investment tip?', true);
            }, 500);
            return null;
        }
        else if (inputLower.includes('save') || inputLower.includes('saving')) {
            this.lastCategory = 'savingStrategies';
            const tip = `Here's a saving strategy: ${this.getRandomTip('savingStrategies')}`;
            this.addMessage(tip, true);
            setTimeout(() => {
                this.addMessage('Would you like another saving tip?', true);
            }, 500);
            return null;
        }
        else if (inputLower.includes('debt') || inputLower.includes('loan')) {
            this.lastCategory = 'debtManagement';
            const tip = `About debt management: ${this.getRandomTip('debtManagement')}`;
            this.addMessage(tip, true);
            setTimeout(() => {
                this.addMessage('Would you like another tip about managing debt?', true);
            }, 500);
            return null;
        }
        else {
            this.lastCategory = null;
            return `I'm not quite sure what you're asking about. I can help you with:

System Features:
• Adding transactions
• Managing accounts
• Setting up budgets
• Viewing reports
• Organizing categories

Financial Advice:
• Budgeting tips
• Investment strategies
• Saving techniques
• Debt management

Please try asking your question differently or choose from these topics!`;
        }
    }

    handleSystemQuestion(inputLower) {
        // Help with adding transactions
        if (inputLower.includes('add transaction') || inputLower.includes('new transaction') || inputLower.includes('create transaction')) {
            return `Adding a New Transaction:

1. Click the "Add Transaction" button at the top of your dashboard
2. Choose the transaction type (Income or Expense)
3. Enter the amount
4. Select a category
5. Add a description
6. Pick the date
7. Choose the account

Need more specific guidance? Just ask!`;
        }

        // Help with managing accounts
        if (inputLower.includes('add account') || inputLower.includes('new account') || inputLower.includes('create account')) {
            return `Creating a New Account:

Available Account Types:
• Checking Account
• Savings Account
• Credit Card
• Cash
• E-Wallet
• Investment

To add one:
1. Find the "Add Account" card in the accounts section
2. Choose your account type
3. Enter the initial balance
4. Select your preferred currency

Would you like to know more about any specific account type?`;
        }

        // Help with budgets
        if (inputLower.includes('create budget') || inputLower.includes('set budget') || inputLower.includes('add budget')) {
            return `Setting Up a Budget:

1. Locate the "Add Budget" card
2. Choose a category to budget for
3. Set your spending limit
4. Define the time period

Your budget will show:
• Spending progress bar
• Amount spent so far
• Time remaining
• Visual indicators for status

Need help with specific budget settings?`;
        }

        // Help with categories
        if (inputLower.includes('categor')) {
            return `Understanding Categories:

Types of Categories:
• Income Categories
• Expense Categories

Features:
• Track spending by category
• Color-coded for easy viewing
• View category-wise reports
• Customize category names

Would you like to learn about creating or managing categories?`;
        }

        // Help with reports and tracking
        if (inputLower.includes('report') || inputLower.includes('track') || inputLower.includes('analysis') || inputLower.includes('summary')) {
            return `Financial Reports & Tracking:

Dashboard Shows:
• Total Balance
• Income Overview
• Expense Breakdown
• Budget Progress

Charts & Analysis:
• Monthly Comparisons
• Category-wise Spending
• Time-based Analysis
• Income Trends

Need help understanding specific reports?`;
        }

        // Help with navigation
        if (inputLower.includes('find') || inputLower.includes('where') || inputLower.includes('how to get to')) {
            return `Navigation Guide:

Main Menu Sections:
• Dashboard - Your financial overview
• Categories - Organize transactions
• Budgets - Set spending limits
• Transactions - View all entries
• Accounts - Manage your accounts
• Profile - Personal settings

Each section is easily accessible from the left sidebar!
Which section would you like to explore?`;
        }

        // General system help
        if (inputLower.includes('help') || inputLower.includes('how to use') || inputLower.includes('what can you do')) {
            return `Welcome to Spendora! Here's what I can help you with:

System Features:
• Adding & managing transactions
• Setting up accounts
• Creating budgets
• Understanding reports
• Organizing categories

Financial Guidance:
• Budgeting advice
• Investment tips
• Saving strategies
• Debt management

What would you like to learn about? I'm here to help!`;
        }

        return null; // Return null if no system-related question is matched
    }

    sendMessage() {
        const inputField = document.getElementById('user-input');
        const text = inputField.value.trim();
        if (text) {
            this.addMessage(text, false);
            inputField.value = '';

            // Simulate bot thinking
            setTimeout(() => {
                const response = this.generateResponse(text);
                if (response) { // Only add message if response is not null
                    this.addMessage(response, true);
                }
            }, 1000);
        }
    }

    sendWelcomeMessage() {
        setTimeout(() => {
            const currentHour = new Date().getHours();
            let greeting;
            
            if (currentHour < 12) {
                greeting = "Good morning";
            } else if (currentHour < 18) {
                greeting = "Good afternoon";
            } else {
                greeting = "Good evening";
            }
            
            this.addMessage(`${greeting}, ${this.userName}! 

I'm Spendora, your personal financial advisor and system assistant. How can I help you today?`, true);
            
            setTimeout(() => {
                this.addMessage(`I'm here to help you with:

System Features
• Adding & managing transactions
• Setting up accounts
• Creating budgets
• Understanding reports & charts

Financial Advice
• Budgeting tips
• Investment strategies
• Saving techniques
• Debt management

What would you like to explore? Just ask!`, true);
            }, 500);
        }, 500);
    }

    toggleQuickQuestions() {
        const panel = document.getElementById('quick-questions-panel');
        this.isQuickQuestionsVisible = !this.isQuickQuestionsVisible;
        panel.classList.toggle('hidden', !this.isQuickQuestionsVisible);
    }

    hideQuickQuestions() {
        const panel = document.getElementById('quick-questions-panel');
        this.isQuickQuestionsVisible = false;
        panel.classList.add('hidden');
    }

    handleQuickQuestion(question) {
        // Hide the quick questions panel
        this.hideQuickQuestions();
        
        // Set the question in the input field
        const inputField = document.getElementById('user-input');
        inputField.value = question;
        
        // Send the message
        this.sendMessage();
    }
} 