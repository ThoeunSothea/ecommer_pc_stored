<?php
define('ALLOW_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$db   = new Database();
$conn = $db->getConnection();

// 1) Load all categories
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");

// 2) Preload counts per category
$countsStmt = $conn->prepare("
  SELECT category_id, COUNT(*) AS cnt
  FROM products
  WHERE status = 'active'
  GROUP BY category_id
");
$countsStmt->execute();
$res = $countsStmt->get_result();
$counts = [];
while ($row = $res->fetch_assoc()) {
    $counts[(int)$row['category_id']] = (int)$row['cnt'];
}
$countsStmt->close();
$conn->close();
?>
<style>
    
      /* Container */
  /* Container */
  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  /* Breadcrumb */
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

  /* Title */
  .page-title {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    color: #333;
  }

  /* Search Box */
  .category-search {
    text-align: center;
    margin-bottom: 1rem;
  }
  .search-input-wrapper {
    position: relative;
    display: inline-block;
    max-width: 400px;
    width: 100%;
  }
  .search-input-wrapper input {
    width: 100%;
    padding: 0.5rem 2rem 0.5rem 1rem;
    border: 1px solid #ccc;
    border-radius: 0.25rem;
    font-size: 1rem;
    font-family: 'Khmer OS', 'Arial', sans-serif;
  }
  #catClear {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    font-size: 1rem;
    cursor: pointer;
    display: none;
  }
  .search-input-wrapper input:not(:placeholder-shown) + #catClear {
    display: block;
  }
  .no-results {
    text-align: center;
    color: #666;
    margin: 1rem 0;
    width: 100%;
    height: 17vh;
  }

  /* Cards Grid */
  .category-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1.5rem;
    list-style: none;
    padding: 0;
    font-family: 'Khmer OS', 'Arial', sans-serif;
  }
  .category-cards .card {
    background: #fff;
    border-radius: 0.5rem;
    overflow: hidden;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    outline: none;
  }
  .category-cards .card:hover,
  .category-cards .card:focus {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    outline: 2px solid #3498db;
    outline-offset: 4px;
  }

  /* Thumbnail */
  .category-cards .thumb {
    width: 100%;
    height: 120px;
    background: #f9f9f9;
  }
  .category-cards .thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  /* Title & Badge */
  .category-cards h3 {
    margin: 0.75rem 0 0.5rem;
    font-size: 1.1rem;
    color: #333;
  }
  .category-cards .badge {
    display: inline-block;
    background: #3498db;
    color: #fff;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.85rem;
    margin-bottom: 1rem;
  }

  /* Responsive */
  @media (max-width: 600px) {
    .search-input-wrapper input {
      width: 90%;
    }
    .category-cards {
      grid-template-columns: 1fr;
    }
  }


</style>

<br>
    <div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <ol>
        <li><a href="<?= BASE_URL ?>/pages/main.php">ទំព័រដើម</a></li>
        <li class="active" aria-current="page">ប្រភេទផលិតផល</li>
        </ol>
    </nav>

    <h1 class="page-title">ប្រភេទផលិតផល</h1>

    <!-- Search box -->
    <div class="category-search" role="search">
        <div class="search-input-wrapper">
        <input
            type="search"
            id="catSearch"
            placeholder="ស្វែងរកប្រភេទ…"
            aria-label="Search categories"
        >
        </div>
    </div>

    <p class="no-results" id="noResults" hidden>មិនមានប្រភេទណាមួយឆ្លើយតប!</p>

    <!-- Category cards -->
    <ul class="category-cards">
        <?php foreach ($categories as $c): 
        $id     = (int)$c['category_id'];
        $name   = htmlspecialchars($c['name'], ENT_QUOTES);
        $rawImg = $c['image'] ?? 'default-cat.png';
        $img    = htmlspecialchars($rawImg, ENT_QUOTES);
        $cnt    = $counts[$id] ?? 0;
        ?>
        <li class="card" tabindex="0">
          <a href="<?= BASE_URL ?>/pages/products.php?category_id=<?= $id ?>">
              <div class="thumb">       
                  <?php if (!empty($c['image_path'])): ?>
                      <img src="/MID-EXAM/ecommerce-system/<?= $c['image_path'] ?>" alt="រូបភាព" >
                  <?php else: ?> គ្មាន <?php endif; ?>
              </div>
                <h3><?= $name ?></h3>
                <span class="badge"><?= number_format($cnt) ?>+</span>
          </a>
        </li>
        <?php endforeach; ?>
    </ul>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
    const input     = document.getElementById('catSearch');
    const clearBtn  = document.getElementById('catClear');
    const cards     = document.querySelectorAll('.category-cards .card');
    const noResults = document.getElementById('noResults');

    function filterCategories() {
        const term = input.value.toLowerCase().trim();
        let anyVisible = false;

        cards.forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        const match = title.includes(term);
        card.style.display = match ? '' : 'none';
        anyVisible ||= match;
        });

        noResults.hidden = anyVisible || term === '';
    }

    function debounce(fn, delay) {
        let t;
        return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), delay);
        };
    }

    input.addEventListener('input', debounce(filterCategories, 200));
    clearBtn.addEventListener('click', () => {
        input.value = '';
        filterCategories();
        input.focus();
    });
    </script>

