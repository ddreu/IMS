<?php
// Comments configuration
$comments_file = 'comments.json';
$MAX_COMMENTS = 500;  // Maximum number of comments to store
$MAX_COMMENT_AGE = 365;  // Days to keep comments

// Function to load comments
function loadComments() {
    global $comments_file;
    if (!file_exists($comments_file)) {
        return [];
    }
    $comments_json = file_get_contents($comments_file);
    return json_decode($comments_json, true) ?: [];
}

// Function to save comments with pruning
function saveComments($comments) {
    global $comments_file, $MAX_COMMENTS, $MAX_COMMENT_AGE;
    
    // Sort comments by timestamp (newest first)
    usort($comments, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Prune old comments
    $current_time = time();
    $comments = array_filter($comments, function($comment) use ($current_time, $MAX_COMMENT_AGE) {
        $comment_time = strtotime($comment['timestamp']);
        return ($current_time - $comment_time) <= ($MAX_COMMENT_AGE * 24 * 60 * 60);
    });
    
    // Limit total comments
    $comments = array_slice($comments, 0, $MAX_COMMENTS);
    
    // Save pruned comments
    $comments_json = json_encode($comments, JSON_PRETTY_PRINT);
    file_put_contents($comments_file, $comments_json);
}

// Check if this is an AJAX request for comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'delete_comments') {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? '';
        $comments = loadComments();

        switch ($action) {
            case 'clear_all':
                $comments = []; // Empty the comments array
                break;
            case 'clear_last':
                if (!empty($comments)) {
                    array_shift($comments); // Remove the first (most recent) comment
                }
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit();
        }

        saveComments($comments);
        echo json_encode(['success' => true, 'message' => 'Comments deleted successfully']);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting comments: ' . $e->getMessage()]);
        exit();
    }
}

// Check if this is an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
    $name = trim($_POST['name'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    header('Content-Type: application/json');

    if ($name && $comment) {
        $comments = loadComments();
        $new_comment = [
            'name' => htmlspecialchars($name),
            'comment' => htmlspecialchars($comment),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $comments[] = $new_comment;
        saveComments($comments);
        
        echo json_encode([
            'success' => true,
            'name' => $new_comment['name'],
            'comment' => $new_comment['comment'],
            'timestamp' => $new_comment['timestamp']
        ]);
        exit();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid input'
        ]);
        exit();
    }
}

// Load comments for display
$comments = loadComments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liane Nichole: Full Billboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
    font-family: 'Roboto', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f9f7f5;
    line-height: 1.8;
    color: #333;
}
.navbar {
    background-color: #e44d26;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}
.navbar-brand {
    font-size: 1.5em;
    font-weight: bold;
    text-decoration: none;
    color: white;
}
.navbar-links a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
    transition: color 0.3s ease;
}
.navbar-links a:hover {
    color: #f1f1f1;
}
.content-wrapper {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    padding-top: 80px; /* Adjust for fixed navbar */
}
.back-button {
    display: inline-block;
    background-color: #e44d26;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    margin: 20px 0;
    transition: background-color 0.3s ease;
}
.back-button:hover {
    background-color: #c0392b;
}
.billboard-container {
    background-color: white;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}
.billboard-image {
    width: 100%;
    max-height: 600px;
    object-fit: cover;
}
.billboard-content {
    padding: 30px;
}
.billboard-title {
    text-align: center;
    color: #e44d26;
    border-bottom: 2px solid #e44d26;
    padding-bottom: 15px;
    margin-bottom: 20px;
}
.billboard-description {
    text-align: justify;
    margin-bottom: 30px;
}
.comments-section {
    background-color: #f1f1f1;
    padding: 20px;
    border-radius: 10px;
}
.comments-title {
    color: #e44d26;
    border-bottom: 2px solid #e44d26;
    padding-bottom: 10px;
    margin-bottom: 20px;
}
.comment {
    background-color: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.comment-author {
    font-weight: bold;
    color: #666;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
}
.comment-timestamp {
    color: #999;
    font-size: 0.8em;
}
.comment-form {
    margin-top: 20px;
}
.comment-form input, 
.comment-form textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.comment-form button {
    background-color: #e44d26;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.comment-form button:hover {
    background-color: #c0392b;
}
#comment-success {
    background-color: #4CAF50;
    color: white;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    display: none;
}
#comment-error {
    background-color: #f44336;
    color: white;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    display: none;
}
    </style>
    <script>
        // Global functions for clearing messages
        window.clearSuccessMessage = function() {
            const successMessage = document.getElementById('comment-success');
            successMessage.textContent = '';
            successMessage.style.display = 'none';
        };

        window.clearErrorMessage = function() {
            const errorMessage = document.getElementById('comment-error');
            errorMessage.textContent = '';
            errorMessage.style.display = 'none';
        };

        window.clearAllMessages = function() {
            window.clearSuccessMessage();
            window.clearErrorMessage();
        };

        // Global functions for managing comments
        window.clearAllComments = function() {
            const formData = new FormData();
            formData.append('ajax', 'delete_comments');
            formData.append('action', 'clear_all');

            fetch('billboard-view.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentSection = document.querySelector('.comments-section');
                    const comments = commentSection.querySelectorAll('.comment');
                    comments.forEach(comment => comment.remove());
                    
                    const successMessage = document.getElementById('comment-success');
                    successMessage.textContent = 'All comments deleted successfully!';
                    successMessage.style.display = 'block';
                } else {
                    const errorMessage = document.getElementById('comment-error');
                    errorMessage.textContent = data.message || 'Failed to delete comments';
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMessage = document.getElementById('comment-error');
                errorMessage.textContent = 'An error occurred while deleting comments';
                errorMessage.style.display = 'block';
            });
        };

        window.clearLastComment = function() {
            const formData = new FormData();
            formData.append('ajax', 'delete_comments');
            formData.append('action', 'clear_last');

            fetch('billboard-view.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentSection = document.querySelector('.comments-section');
                    const firstComment = commentSection.querySelector('.comment');
                    if (firstComment) {
                        firstComment.remove();
                    }
                    
                    const successMessage = document.getElementById('comment-success');
                    successMessage.textContent = 'Last comment deleted successfully!';
                    successMessage.style.display = 'block';
                } else {
                    const errorMessage = document.getElementById('comment-error');
                    errorMessage.textContent = data.message || 'Failed to delete last comment';
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMessage = document.getElementById('comment-error');
                errorMessage.textContent = 'An error occurred while deleting the last comment';
                errorMessage.style.display = 'block';
            });
        };

        window.getCommentCount = function() {
            const commentSection = document.querySelector('.comments-section');
            const comments = commentSection.querySelectorAll('.comment');
            return comments.length;
        };

        function submitComment(event) {
            event.preventDefault();
            
                const nameInput = document.querySelector('input[name="name"]');
            const commentInput = document.querySelector('textarea[name="comment"]');
            const commentSection = document.querySelector('.comments-section');
            const successMessage = document.getElementById('comment-success');
            const errorMessage = document.getElementById('comment-error');

            const formData = new FormData();
            formData.append('name', nameInput.value);
            formData.append('comment', commentInput.value);
            formData.append('ajax', 'true');  // Add AJAX flag

            fetch('billboard-view.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create new comment element
                    const newComment = document.createElement('div');
                    newComment.classList.add('comment');
                    newComment.innerHTML = `
                        <div class="comment-author">
                            ${data.name}
                            <span class="comment-timestamp">${data.timestamp}</span>
                        </div>
                        <p>${data.comment}</p>
                    `;

                    // Insert new comment at the top
                    const firstComment = commentSection.querySelector('.comment');
                    if (firstComment) {
                        commentSection.insertBefore(newComment, firstComment);
                    } else {
                        commentSection.appendChild(newComment);
                    }

                    // Clear inputs
                    nameInput.value = '';
                    commentInput.value = '';

                    // Show success message
                    successMessage.textContent = 'Comment posted successfully!';
                    successMessage.style.display = 'block';
                } else {
                    // Show error message
                    errorMessage.textContent = data.message || 'Failed to post comment';
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessage.textContent = 'An error occurred';
                errorMessage.style.display = 'block';
            });
        }
    </script>
</head>
<body>
    <!--<nav class="navbar">
        <a href="#" class="navbar-brand">Liane Nichole</a>
        <div class="navbar-links">
            <a href="billboard.php">Article</a>
            <a href="#">Home</a>
            <a href="#">About</a>
            <a href="#">Contact</a>
        </div>
    </nav>-->

    <div class="content-wrapper">
        <a href="article.php" class="back-button">← Back to Article</a>

        <div class="billboard-container">
            <img src="liane.png" alt="Liane Nichole Full Billboard" class="billboard-image">
            
            <div class="billboard-content">
                <div class="billboard-title">
                    <h1>Liane Nichole: A Radiant Soul</h1>
                </div>
                
                <div class="billboard-description">
                    <p>In the tapestry of human beauty, Liane Nichole emerges as a masterpiece of grace, intelligence, and compassion. Her beauty is not merely a physical attribute, but a profound reflection of her inner luminosity. With eyes that sparkle with dreams and a smile that could illuminate the darkest corners, she represents the epitome of modern elegance.</p>
                    
                    <p>Beyond her stunning exterior, Liane embodies a spirit that transcends conventional definitions of beauty. Her intellect, kindness, and unwavering determination create a magnetic aura that draws people towards her. She is not just seen, but deeply felt - a beacon of inspiration in a world that often overlooks the true essence of beauty.</p>
                </div>
            </div>

            <div class="comments-section">
                <div class="comments-title">
                    <h2>Celebrating Liane's Beauty</h2>
                </div>

                <div id="comment-success"></div>
                <div id="comment-error"></div>

                <?php foreach (array_reverse($comments) as $comment): ?>
                    <div class="comment">
                        <div class="comment-author">
                            <?php echo $comment['name']; ?>
                            <span class="comment-timestamp"><?php echo $comment['timestamp']; ?></span>
                        </div>
                        <p><?php echo $comment['comment']; ?></p>
                    </div>
                <?php endforeach; ?>

                <form onsubmit="submitComment(event)" class="comment-form">
                    <input type="text" name="name" placeholder="Your Name" required>
                    <textarea name="comment" placeholder="Share your thoughts about Liane's beauty" required></textarea>
                    <button type="submit">Post Comment</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>