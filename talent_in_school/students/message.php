<?php
session_start();
include "../component/connect.php";

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Get student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $group_id = $_POST['group_id'];
    $message = trim($_POST['message']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL;
    
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (group_id, student_id, parent_message_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$group_id, $student_id, $parent_id, $message]);
    }
}

// Handle group selection
$selected_group = isset($_GET['group_id']) ? $_GET['group_id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Chat Groups</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            overflow: hidden;
        }

        .chat-container {
            display: flex;
            height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 300px;
            background: #2c3e50;
            color: white;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .user-info {
            padding: 20px;
            background: #34495e;
            text-align: center;
            border-bottom: 1px solid #3d566e;
        }

        .user-info h3 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .user-info p {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .group-list {
            flex: 1;
            padding: 20px 0;
        }

        .group-item {
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .group-item:hover {
            background: #34495e;
        }

        .group-item.active {
            background: #34495e;
            border-left-color: #3498db;
        }

        .group-icon {
            width: 40px;
            height: 40px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .group-info {
            flex: 1;
        }

        .group-name {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .group-description {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        /* Main Chat Area */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
        }

        .chat-header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .chat-header h2 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .chat-header p {
            color: #7f8c8d;
            font-size: 0.85rem;
        }

        /* Messages Area */
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-main {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
        }

        .message-main.own-message {
            background: #e3f2fd;
            border-right: 3px solid #2196f3;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .message-sender {
            font-weight: 600;
            color: #2c3e50;
        }

        .message-time {
            font-size: 0.7rem;
            color: #95a5a6;
        }

        .message-text {
            color: #333;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .message-actions {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .reply-btn, .view-replies-btn {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 0.8rem;
            padding: 5px 0;
            transition: color 0.3s;
        }

        .reply-btn:hover, .view-replies-btn:hover {
            color: #2980b9;
        }

        /* Replies Section */
        .replies-section {
            margin-top: 15px;
            padding-left: 30px;
            border-left: 2px solid #3498db;
            display: none;
        }

        .replies-section.show {
            display: block;
        }

        .reply-message {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .reply-sender {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.8rem;
            margin-bottom: 5px;
        }

        /* Message Input */
        .message-input-area {
            background: white;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .reply-indicator {
            background: #e8f4f8;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: none;
            align-items: center;
            justify-content: space-between;
        }

        .reply-indicator.show {
            display: flex;
        }

        .reply-indicator span {
            font-size: 0.85rem;
            color: #3498db;
        }

        .cancel-reply {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-weight: bold;
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        .message-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .message-input:focus {
            border-color: #3498db;
        }

        .send-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .send-btn:hover {
            transform: translateY(-2px);
        }

        /* No Group Selected */
        .no-group {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #95a5a6;
        }

        .no-group i {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        /* Scrollbar */
        .messages-area::-webkit-scrollbar,
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .messages-area::-webkit-scrollbar-track,
        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .messages-area::-webkit-scrollbar-thumb,
        .sidebar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .messages-area::-webkit-scrollbar-thumb:hover,
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
            }
            
            .chat-header h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <!-- Sidebar with groups -->
        <div class="sidebar">
            <div class="user-info">
                <h3><i class="fas fa-user-graduate"></i> <?= htmlspecialchars($student['name'] ?? 'Student') ?></h3>
                <p>Online</p>
            </div>
            
            <div class="group-list">
                <?php
                // Get all groups the student is member of
                $stmt = $pdo->prepare("
                    SELECT g.* FROM chat_groups g
                    INNER JOIN group_members gm ON g.id = gm.group_id
                    WHERE gm.student_id = ?
                    ORDER BY g.group_name ASC
                ");
                $stmt->execute([$student_id]);
                $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($groups as $group):
                    $active_class = ($selected_group == $group['id']) ? 'active' : '';
                ?>
                <div class="group-item <?= $active_class ?>" onclick="window.location.href='?group_id=<?= $group['id'] ?>'">
                    <div class="group-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="group-info">
                        <div class="group-name"><?= htmlspecialchars($group['group_name']) ?></div>
                        <div class="group-description"><?= htmlspecialchars($group['description']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($groups)): ?>
                <div style="padding: 20px; text-align: center; opacity: 0.7;">
                    <i class="fas fa-users"></i>
                    <p>No groups available</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Chat Area -->
        <?php if($selected_group): 
            // Get group info
            $stmt = $pdo->prepare("SELECT * FROM chat_groups WHERE id = ?");
            $stmt->execute([$selected_group]);
            $current_group = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($current_group):
        ?>
        <div class="chat-area">
            <div class="chat-header">
                <h2><i class="fas fa-hashtag"></i> <?= htmlspecialchars($current_group['group_name']) ?></h2>
                <p><?= htmlspecialchars($current_group['description']) ?></p>
            </div>
            
            <div class="messages-area" id="messagesArea">
                <?php
                // Fetch main messages (not replies)
                $stmt = $pdo->prepare("
                    SELECT m.*, s.name as student_name 
                    FROM messages m
                    INNER JOIN students s ON m.student_id = s.id
                    WHERE m.group_id = ? AND m.parent_message_id IS NULL
                    ORDER BY m.created_at ASC
                ");
                $stmt->execute([$selected_group]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($messages as $message):
                    $is_own = ($message['student_id'] == $student_id);
                    
                    // Fetch replies for this message
                    $reply_stmt = $pdo->prepare("
                        SELECT m.*, s.name as student_name 
                        FROM messages m
                        INNER JOIN students s ON m.student_id = s.id
                        WHERE m.parent_message_id = ?
                        ORDER BY m.created_at ASC
                    ");
                    $reply_stmt->execute([$message['id']]);
                    $replies = $reply_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="message" data-message-id="<?= $message['id'] ?>">
                    <div class="message-main <?= $is_own ? 'own-message' : '' ?>">
                        <div class="message-header">
                            <span class="message-sender">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($message['student_name']) ?>
                            </span>
                            <span class="message-time">
                                <?= date('M d, H:i', strtotime($message['created_at'])) ?>
                            </span>
                        </div>
                        <div class="message-text">
                            <?= nl2br(htmlspecialchars($message['message'])) ?>
                        </div>
                        <div class="message-actions">
                            <button class="reply-btn" onclick="showReplyForm(<?= $message['id'] ?>, '<?= htmlspecialchars($message['student_name']) ?>')">
                                <i class="fas fa-reply"></i> Reply
                            </button>
                            <?php if(!empty($replies)): ?>
                            <button class="view-replies-btn" onclick="toggleReplies(<?= $message['id'] ?>)">
                                <i class="fas fa-comments"></i> View Replies (<?= count($replies) ?>)
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="replies-section" id="replies-<?= $message['id'] ?>">
                        <?php foreach($replies as $reply): ?>
                        <div class="reply-message">
                            <div class="reply-sender">
                                <i class="fas fa-reply-all"></i> <?= htmlspecialchars($reply['student_name']) ?>
                                <span style="font-size: 0.7rem; color: #95a5a6;"><?= date('M d, H:i', strtotime($reply['created_at'])) ?></span>
                            </div>
                            <div><?= nl2br(htmlspecialchars($reply['message'])) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($messages)): ?>
                <div style="text-align: center; padding: 40px; color: #95a5a6;">
                    <i class="fas fa-comment-dots" style="font-size: 3rem; margin-bottom: 10px;"></i>
                    <p>No messages yet. Start the conversation!</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="message-input-area">
                <div class="reply-indicator" id="replyIndicator">
                    <span><i class="fas fa-reply"></i> Replying to <span id="replyToName"></span></span>
                    <button class="cancel-reply" onclick="cancelReply()">× Cancel</button>
                </div>
                <form method="POST" action="" id="messageForm">
                    <input type="hidden" name="group_id" value="<?= $selected_group ?>">
                    <input type="hidden" name="parent_id" id="parentId" value="">
                    <div class="input-group">
                        <input type="text" name="message" class="message-input" placeholder="Type your message..." required autocomplete="off">
                        <button type="submit" name="send_message" class="send-btn">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
            // Auto-scroll to bottom of messages
            const messagesArea = document.getElementById('messagesArea');
            messagesArea.scrollTop = messagesArea.scrollHeight;
            
            // Reply functionality
            function showReplyForm(messageId, senderName) {
                document.getElementById('parentId').value = messageId;
                document.getElementById('replyToName').innerText = senderName;
                document.getElementById('replyIndicator').classList.add('show');
                document.querySelector('.message-input').focus();
            }
            
            function cancelReply() {
                document.getElementById('parentId').value = '';
                document.getElementById('replyIndicator').classList.remove('show');
            }
            
            function toggleReplies(messageId) {
                const repliesDiv = document.getElementById(`replies-${messageId}`);
                const btn = event.target.closest('.view-replies-btn');
                
                if (repliesDiv.classList.contains('show')) {
                    repliesDiv.classList.remove('show');
                    btn.innerHTML = '<i class="fas fa-comments"></i> View Replies';
                } else {
                    repliesDiv.classList.add('show');
                    const count = repliesDiv.children.length;
                    btn.innerHTML = `<i class="fas fa-comments"></i> Hide Replies (${count})`;
                }
            }
            
            // Auto-refresh messages every 5 seconds
            function refreshMessages() {
                if (window.location.href.includes('group_id=')) {
                    const groupId = <?= $selected_group ?>;
                    fetch(`get_messages.php?group_id=${groupId}`)
                        .then(response => response.text())
                        .then(data => {
                            // Only refresh if there's new content
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = data;
                            const newMessages = tempDiv.querySelector('.messages-area');
                            if (newMessages && newMessages.innerHTML !== messagesArea.innerHTML) {
                                const oldScrollHeight = messagesArea.scrollHeight;
                                const wasAtBottom = messagesArea.scrollTop + messagesArea.clientHeight >= oldScrollHeight - 50;
                                
                                messagesArea.innerHTML = newMessages.innerHTML;
                                
                                if (wasAtBottom) {
                                    messagesArea.scrollTop = messagesArea.scrollHeight;
                                }
                                
                                // Re-attach reply functions
                                attachReplyFunctions();
                            }
                        });
                }
            }
            
            function attachReplyFunctions() {
                // Re-attach event listeners for new buttons
                document.querySelectorAll('.reply-btn').forEach(btn => {
                    const oldOnClick = btn.getAttribute('onclick');
                    if (oldOnClick && !btn.hasAttribute('data-attached')) {
                        btn.setAttribute('data-attached', 'true');
                    }
                });
            }
            
            // Refresh every 5 seconds
            setInterval(refreshMessages, 5000);
            
            // Keep scroll at bottom when new messages arrive
            const observer = new MutationObserver(() => {
                const wasAtBottom = messagesArea.scrollTop + messagesArea.clientHeight >= messagesArea.scrollHeight - 100;
                if (wasAtBottom) {
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                }
            });
            observer.observe(messagesArea, { childList: true, subtree: true });
        </script>
        
        <?php else: ?>
        <div class="no-group">
            <i class="fas fa-comments"></i>
            <h3>Select a group to start chatting</h3>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="no-group">
            <i class="fas fa-users"></i>
            <h3>Select a group from the sidebar to start chatting</h3>
            <p>Join a group to connect with other students</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>