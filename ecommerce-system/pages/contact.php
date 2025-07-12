<?php
define('ALLOW_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';


$message_sent = false;
$error_msg    = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error_msg = 'សូម​បំពេញ​ទាំងអស់​វាល.';
    } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'អ៊ីមែល​មិន​ត្រឹមត្រូវ.';
    } else {
        
        $message_sent = true;
    }
}
?>
<style>
    
  .contact-page {
    max-width: 800px;
    margin: 3rem auto;
    padding: 2rem;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border-radius: 0.5rem;
  }

  .breadcrumb-nav ol {
    display: flex;
    list-style: none;
    padding: 0;
    margin-bottom: 1rem;
    font-size: 0.9rem;
  }
  .breadcrumb-nav li + li:before {
    content: "›";
    margin: 0 0.5rem;
    color: #888;
  }
  .breadcrumb-nav a {
    color: #3498db;
    text-decoration: none;
  }
  .breadcrumb-nav .active {
    color: #555;
  }

  .contact-page .page-title {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 1.5rem;
    color: #2c3e50;
  }


  .alert {
    padding: 0.75rem 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    position: relative;
    transition: opacity 0.3s ease;
  }
  .alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }
  .alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }
  .alert .close-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.75rem;
    background: none;
    border: none;
    font-size: 1.2rem;
    line-height: 1;
    cursor: pointer;
    color: inherit;
  }


  .contact-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
  }
 

  .contact-info h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #333;
  }
  .contact-info p {
    margin: 0.5rem 0;
    font-size: 1rem;
    color: #555;
  }


  .contact-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }
  .contact-form .form-group {
    display: flex;
    flex-direction: column;
  }
  .contact-form label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #555;
  }
  .contact-form input,
  .contact-form textarea {
    padding: 0.75rem 1rem;
    border: 1px solid #ccc;
    border-radius: 0.25rem;
    font-size: 1rem;
    transition: border-color 0.3s ease;
  }
  .contact-form input:focus,
  .contact-form textarea:focus {
    border-color: #3498db;
    outline: none;
  }


  .contact-form .btn-primary {
    align-self: flex-start;
    padding: 0.75rem 1.5rem;
    background: #3498db;
    color: #ffffff;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.3s ease;
    font-family: 'Khmer OS', 'Arial', sans-serif;
    
  }
  .contact-form .btn-primary:hover {
    background: #2980b9;
  }
 @media (max-width: 700px) {
    .contact-content {
      grid-template-columns: 1fr;
    }
  }

</style>
<br>
<div class="container contact-page">

  <nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol>
      <li><a href="<?= BASE_URL ?>/pages/main.php">ទំព័រដើម</a></li>
      <li class="active" aria-current="page">ទាក់ទងមកយើង</li>
    </ol>
  </nav>

  <h1 class="page-title">ទាក់ទងមកយើង</h1>

  <?php if ($message_sent): ?>
    <div class="alert alert-success">សាររបស់អ្នកត្រូវបានផ្ញើរួចហើយ!</div>
  <?php elseif ($error_msg): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_msg, ENT_QUOTES) ?></div>
  <?php endif; ?>

  <div class="contact-content">
    <!-- contact info panel -->
    <div class="contact-info">
      <h2>ព័ត៌មានទំនាក់ទំនង</h2>
      <p><strong>អាសយដ្ឋាន:</strong> 123 ផ្លូវបឹងកេងកង, ភ្នំពេញ, កម្ពុជា</p>
      <p><strong>ទូរស័ព្ទ:</strong> +855 12 345 678 </p>
      <p><strong>ទូរស័ព្ទ:</strong> +855 99 389 347 </p>
      <p><strong>ទូរស័ព្ទ:</strong> +855 69 754 373 </p>
      <p><strong>អ៊ីមែល:</strong> brosothea79@gmail.com</p>
      <p><strong>ម៉ោង:</strong> ច័ន្ទ–សុក្រ 7:00–22:00</p>
    </div>

    <!-- contact form -->
    <form action="contact.php" method="POST" class="contact-form">
      <div class="form-group">
        <label for="name">ឈ្មោះ</label>
        <input id="name" type="text" name="name"
               value="<?= htmlspecialchars($name ?? '', ENT_QUOTES) ?>"
               required>
      </div>
      <div class="form-group">
        <label for="email">អ៊ីមែល</label>
        <input id="email" type="email" name="email"
               value="<?= htmlspecialchars($email ?? '', ENT_QUOTES) ?>"
               required>
      </div>
      <div class="form-group">
        <label for="message">សារ</label>
        <textarea id="message" name="message" rows="6" required><?= htmlspecialchars($message ?? '', ENT_QUOTES) ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">ផ្ញើសារ</button>

    </form>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const form       = document.querySelector('.contact-form');
    const alerts     = document.querySelectorAll('.alert');
    const closeBtns  = [];


    alerts.forEach(alert => {
      const btn = document.createElement('button');
      btn.innerHTML = '&times;';
      btn.className  = 'close-btn';
      btn.setAttribute('aria-label', 'Close alert');
      alert.appendChild(btn);
      closeBtns.push({alert, btn});
      setTimeout(() => fadeOut(alert), 5000);
    });


    closeBtns.forEach(({alert, btn}) => {
      btn.addEventListener('click', () => fadeOut(alert));
    });


    form.addEventListener('submit', e => {
      let valid = true;
      form.querySelectorAll('input, textarea').forEach(field => {
        if (!field.checkValidity()) {
          valid = false;
          field.classList.add('invalid');
        } else {
          field.classList.remove('invalid');
        }
      });
      if (!valid) {
        e.preventDefault();
        alert('សូមបំពេញវាលដែលសម្គាល់ថាត្រូវបានទាមទារ។');
      }
    });


    function fadeOut(el) {
      el.style.opacity = '1';
      const fade = setInterval(() => {
        if ((el.style.opacity -= 0.1) <= 0) {
          clearInterval(fade);
          el.remove();
        }
      }, 50);
    }
  });

</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
