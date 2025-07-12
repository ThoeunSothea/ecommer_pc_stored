<?php
define('ALLOW_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';
?>
<style>

  .about-page {
    max-width: 800px;
    margin: 3rem auto;
    padding: 2rem;
    background: #fff;
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
  .about-page h1 {
    font-size: 2rem;
    margin-bottom: 1rem;
    text-align: center;
    color: #2c3e50;
  }
  .about-page p {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1rem;
    color: #555;
  }

  .about-page p + p {
    padding-top: 1rem;
  }

  .team-section {
    margin-top: 3rem;
  }
  .team-section h2 {
    text-align: center;
    font-size: 2rem;
    color: #333;
    margin-bottom: 2rem;
  }


  .team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px,1fr));
    gap: 2rem;
  }


  .team-member {
    text-align: center;
  }
  .team-member img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 1rem;
  }
  .team-member h3 {
    font-size: 1.25rem;
    color: #2c3e50;
    margin-bottom: 0.25rem;
  }
  .team-member .role {
    font-size: 1rem;
    color: #777;
    margin-bottom: 0.75rem;
    font-style: italic;
  }
  .team-member .bio {
    font-size: 0.95rem;
    color: #555;
    line-height: 1.4;
  }


  @media (max-width: 600px) {
    .about-page {
      margin: 2rem 1rem;
      padding: 1.5rem;
    }
    .team-grid {
      grid-template-columns: 1fr;
    }
  }

  .team-member {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.6s ease-out, transform 0.6s ease-out;
  }


  .team-member.visible {
    opacity: 1;
    transform: translateY(0);
  }

</style>

<br>
<div class="container about-page">
  <nav aria-label="breadcrumb" class="breadcrumb-nav">
      <ol>
        <li><a href="<?= BASE_URL ?>/pages/main.php">ទំព័រដើម</a></li>
        <li class="active" aria-current="page">អំពីយើង</li>
      </ol>
    </nav>
  <h1>អំពីយើង</h1>
  <p>នេះជាកន្លែងផ្ដល់ពត៌មានអំពីក្រុមហ៊ុន និងបេសកកម្មរបស់យើង។</p>
  <p>យើងបានចាប់ផ្តើមនៅឆ្នាំ 2020 ដើម្បីផ្ដល់ផលិតផលគុណភាពខ្ពស់ដល់អតិថិជន។</p>
  <p>បេសកកម្មរបស់យើងគឺជួយឲ្យអាជីវកម្មរីកចម្រើនតាមរយៈបច្ចេកវិទ្យាអនឡាញ។</p>


  <section class="team-section">
    <h2>ក្រុមរបស់យើង</h2>
    <p>សូមស្វាគមន៍ក្រុមរបស់យើង! យើងជាក្រុមអ្នកឯកទេសដែលមានជំនាញខ្ពស់ក្នុងវិស័យផ្សេងៗ។</p>
    <div class="team-member">
        <img src="../uploads/categories/image_4.png" alt="Thoeun Sothea">
        <h3>Thoeun Sothea</h3>
        <p class="role">Web developer </p>
        <p class="bio">Sothea មានបទពិសោធន៍ជាច្រើនឆ្នាំក្នុងវិស័យបច្ចេកវិទ្យា និងការអភិវឌ្ឍន៍ផលិតផល។</p>
      </div>
    <div class="team-grid">

      <div class="team-member">
        <img src="../uploads/categories/image_3.png" alt="En Sopheak">
        <h3>En Sopheak</h3>
        <p class="role">UX/UI Designer</p>
        <p class="bio">Sopheak ជាអ្នកឯកទេស Full-Stack ដែលដឹកនាំក្រុម Dev ដើម្បីបង្កើតផលិតផលល្អបំផុត។</p>
      </div>

      <div class="team-member">
        <img src="../uploads/categories/image_2.png" alt="Chounsoeun Reaksa">
        <h3>Chounsoeun Reaksa</h3>
        <p class="role">Admin System</p>
        <p class="bio">Reaksa មានបទពិសោធន៍ក្នុងការគ្រប់គ្រងប្រព័ន្ធ និងការអភិវឌ្ឍន៍គម្រោង។</p>
      </div>

      <div class="team-member">
        <img src="../uploads/categories/image_1.png" alt="Suy SouKeang">
        <h3>Suy SouKeang</h3>
        <p class="role">Admin System</p>
        <p class="bio">SouKeang មានបទពិសោធន៍ក្នុងការគ្រប់គ្រងប្រព័ន្ធ និងការអភិវឌ្ឍន៍គម្រោង។</p>
      </div>

    </div>
  </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const members = document.querySelectorAll('.team-member');
  const obsOptions = { threshold: 0.2 };

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        obs.unobserve(entry.target);
      }
    });
  }, obsOptions);

  members.forEach(mem => observer.observe(mem));
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
