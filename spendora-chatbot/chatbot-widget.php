<?php
// Prevent direct access to this file
if (!defined('INCLUDED_IN_DASHBOARD')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access forbidden.');
}

// Get user's name from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'there';
?>

<!-- Spendora Chat Head and Chat Interface -->
<div class="chat-head" id="spendora-chat-head">
    <img src="spendora-chatbot/assets/spendora-avatar.svg" alt="Spendora" class="chat-head-avatar">
    <div class="chat-head-indicator"></div>
</div>

<div class="chat-container" id="spendora-chat">
    <div class="chat-header">
        <div class="header-content">
            <img src="spendora-chatbot/assets/spendora-avatar.svg" alt="Spendora" class="header-avatar">
            <span>Spendora Advisor</span>
        </div>
        <div class="header-actions">
            <button id="minimize-btn">−</button>
        </div>
    </div>
    <div id="chat-body">
        <div id="chat-messages">
        </div>
        
        <!-- Quick Questions Panel -->
        <div id="quick-questions-panel" class="hidden">
            <div class="quick-questions-header">
                <h3>Quick Questions</h3>
                <span>Click any question to ask Spendora</span>
            </div>
            <div class="quick-questions-sections">
                <div class="quick-section">
                    <h4>System Help</h4>
                    <button class="quick-question" data-question="How do I add a new transaction?">
                        How to add a transaction?
                    </button>
                    <button class="quick-question" data-question="Show me how to create an account">
                        How to create an account?
                    </button>
                    <button class="quick-question" data-question="Help me set up a budget">
                        How to set up a budget?
                    </button>
                    <button class="quick-question" data-question="How do I view my reports?">
                        How to view reports?
                    </button>
                </div>
                <div class="quick-section">
                    <h4>Financial Advice</h4>
                    <button class="quick-question" data-question="Give me budgeting tips">
                        Get budgeting tips
                    </button>
                    <button class="quick-question" data-question="How should I invest my money?">
                        Investment advice
                    </button>
                    <button class="quick-question" data-question="Help me save money">
                        Saving strategies
                    </button>
                    <button class="quick-question" data-question="Give me debt management tips">
                        Debt management help
                    </button>
                </div>
            </div>
        </div>

        <div class="chat-input-container">
            <input type="text" id="user-input" placeholder="Ask for financial advice...">
            <!-- Quick Questions Button -->
            <button id="quick-questions-toggle" class="action-btn" title="Show Quick Questions">
                <i class="fas fa-lightbulb"></i>
            </button>
            <button class="send-button">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<!-- Load Spendora Chatbot Scripts -->
<script src="spendora-chatbot/data/financial-datasets.js"></script>
<script src="spendora-chatbot/js/spendora.js"></script>
<script>
    // Initialize Spendora Chatbot with user's name
    document.addEventListener('DOMContentLoaded', () => {
        window.spendoraBot = new SpendoraBot('<?php echo htmlspecialchars($user_name); ?>');
    });
</script> 