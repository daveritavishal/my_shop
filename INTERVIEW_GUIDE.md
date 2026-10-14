# 📚 My Shop - Mini E-Commerce & Product CRUD: Complete Interview Guide

Yeh guide aapke **Full-Stack PHP Developer / Backend Developer** interviews ke liye prepare ki gayi hai. Isme project architecture aur sabhi important questions ko Hindi aur English mix (easy language) me samjhaya gaya hai.

---

## 🏗️ 1. Project Overview & Architecture

- **Project Title:** My Shop (Mini E-Commerce & Product CRUD)
- **Tech Stack:** PHP 8.2, MySQL (MariaDB), PHP PDO, Bootstrap 5.3, JavaScript (ES6 / Fetch API), HTML5, CSS3.
- **Key Modules:**
  1. **Storefront (`index.php`, `products.php`):** Responsive grid, real-time client-side search & category filter, sort by price, quick-view modal.
  2. **Product Details (`product_detail.php`):** Full specs, dynamic quantity selector, stock indicator, related products.
  3. **Cart Engine (`cart.php`, `cart_action.php`):** Session-backed cart (`$_SESSION['cart']`), live badge count, AJAX add-to-cart, instant subtotal & grand total calculation, quantity increment/decrement (+/-).
  4. **Checkout Simulation (`checkout.php`):** Multi-table order processing inside **PDO Transactions** with stock decrement.
  5. **Product CRUD Module (`admin_products.php`, `add_product.php`, `edit_product.php`, `delete_product.php`):**
     - **Create (C):** Add product with secure file upload (JPG/PNG/WEBP/SVG validation).
     - **Read (R):** Tabular list with live inventory stats (Total, Low Stock, Out of Stock).
     - **Update (U):** Pre-loaded edit form, image replacement with old image cleanup.
     - **Delete (D):** Safe deletion with confirmation modal and file unlink.

---

## 🎯 2. Top 15 PHP & PDO Interview Questions & Answers

### Q1: PDO (PHP Data Objects) kya hai? Yeh `mysqli` se behtar kyu hai?
**Answer:**
- **PDO** ek database abstraction layer hai jo PHP me multiple databases (MySQL, PostgreSQL, SQLite, Oracle, etc.) ko connect karne ke liye ek consistent interface provide karti hai.
- **Kyu behtar hai:**
  1. **Database Independence:** `mysqli` sirf MySQL ke sath chalta hai. Agar kal database change karna pade to PDO me sirf connection string (DSN) badalni hoti hai.
  2. **Named Parameters:** PDO me hum `:name`, `:price` jaise readable named parameters use kar sakte hain, jabki `mysqli` me sirf `?` placeholders hote hain.
  3. **Better Exception Handling:** PDO `PDOException` throw karta hai jise hum `try-catch` block me clean handle kar sakte hain.
  4. **Object-Oriented by Default:** Modern PHP standards (OOP) ke sath naturally fit hota hai.

---

### Q2: Prepared Statements kya hote hain aur yeh SQL Injection se kaise bachate hain?
**Answer:**
- **Normal Query:** Database query structure aur user input ko ek sath compile karta hai, jisse attacker malicious SQL inject kar sakta hai (e.g. `' OR '1'='1`).
- **Prepared Statement:**
  1. Pehle query ka structure database engine ke pass bhejkar pre-compile hota hai:
     ```php
     $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
     ```
  2. Uske baad user data separate parameters ke roop me bheja jata hai:
     ```php
     $stmt->execute([':id' => $productId]);
     ```
  Database engine parameters ko hamesha **pure data/string** manta hai, executable code nahi. Is wajah se **SQL Injection 100% prevent ho jata hai**.

---

### Q3: `config/db.php` me PDO options ka kya role hai?
```php
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
```
**Answer:**
1. `ATTR_ERRMODE => ERRMODE_EXCEPTION`: Kisi bhi database error par PHP warning ki jagah `PDOException` throw hoti hai, jise hum catch karke user-friendly error show kar sakte hain.
2. `ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC`: Results hamesha clean associative array (column name => value) me aate hain, duplicate numeric keys nahi banti.
3. `ATTR_EMULATE_PREPARES => false`: PHP ke emulation ki bajaye MySQL server ke genuine native prepared statements use hote hain, jo zyada secure aur reliable hain.

---

### Q4: Database Transactions (`beginTransaction()`, `commit()`, `rollBack()`) kya hote hain?
**Answer:**
Checkout process me hume 2 kaam karne hote hain:
1. `orders` table me row insert karna.
2. `order_items` table me products add karna aur `products` table ka stock ghatana.

Agar pehla step ho gaya aur dusre me error aa gayi, to data corrupt ho jayega.
Isliye hum **ACID properties (Atomicity)** use karte hain:
```php
try {
    $pdo->beginTransaction(); // Transaction start
    
    // Step 1: Insert order
    // Step 2: Insert order items & update stock
    
    $pdo->commit(); // Sab successful hua to permanently save karein
} catch (Exception $e) {
    $pdo->rollBack(); // Agar koi bhi ek step fail hua to pura rollback
}
```

---

### Q5: Shopping Cart ko session me kaise store kiya gaya hai?
**Answer:**
PHP Sessions server-side par user specific data persist karne ke liye use hote hain.
Humne `$_SESSION['cart']` me associative array use kiya hai jisme key product ka `id` hota hai:
```php
$_SESSION['cart'][$productId] = [
    'id'    => $product['id'],
    'name'  => $product['name'],
    'price' => $product['price'],
    'image' => $product['image'],
    'stock' => $product['stock'],
    'qty'   => $quantity
];
```
**Fayde:**
- Kisi product ko check karna ho ki cart me hai ya nahi to `isset($_SESSION['cart'][$id])` se $O(1)$ time me check ho jata hai.
- Page reload ya navigate hone par bhi cart items persist rehte hain.
- Database par baar-baar hit nahi hota jab tak user checkout na kare.

---

### Q6: File Upload karte waqt kin security checks ka dhyan rakhna chahiye?
**Answer (`add_product.php` & `edit_product.php` me implemented):**
1. **Error Check:** `$_FILES['image']['error'] === UPLOAD_ERR_OK`.
2. **File Size Limit:** Check karein file size limit ke andar ho (e.g., max 3MB).
3. **Extension Whitelisting:** Sirf safe image formats allow karein (`['jpg', 'jpeg', 'png', 'webp', 'svg']`). Kabhi bhi `.php`, `.exe`, `.js` allow na karein.
4. **Unique Filename Generation:** Attacker original filename se directory traversal ya overwrite na kare, isliye safe random name generate karein:
   ```php
   $safeName = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
   ```
5. **Safe Storage:** `move_uploaded_file()` se temporary location se target folder me move karein.

---

### Q7: XSS (Cross-Site Scripting) kya hai aur is project me ise kaise roka gaya hai?
**Answer:**
XSS attack me attacker input form ke through malicious JavaScript code (e.g., `<script>stealCookies()</script>`) inject karta hai.
Humne `sanitize()` function banaya hai:
```php
function sanitize($input) {
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}
```
`htmlspecialchars()` special characters jaise `<`, `>`, `"`, `'` ko HTML entities (`&lt;`, `&gt;`, `&quot;`, `&#039;`) me convert kar deta hai, jisse browser unhe code ki tarah execute nahi karta balki text ki tarah render karta hai.

---

### Q8: AJAX Add to Cart vs Regular Page Submit me kya advantage hai?
**Answer:**
- **Regular Submit:** Poora page reload hota hai, scroll position kho jati hai, user experience slow hota hai.
- **AJAX Submit (`assets/js/app.js` + `cart_action.php?action=add_ajax`):**
  JavaScript `fetch()` API background me server ko POST request bhejti hai. Server JSON response return karta hai (`cart_count`, `message`). JS bina page refresh kiye navbar ke cart badge ko update karti hai aur ek sundar Bootstrap Toast popup show karti hai.

---

### Q9: `lastInsertId()` kya kaam karta hai?
**Answer:**
Jab auto-increment primary key wali table me koi nayi row INSERT hoti hai, to `lastInsertId()` us newly created record ki unique ID return karta hai.
Humne `checkout.php` me `orders` table me order insert karne ke baad `lastInsertId()` se `$orderId` nikala aur use `order_items` table me foreign key ke roop me save kiya.

---

### Q10: Client-side Validation aur Server-side Validation dono kyu zaroori hain?
**Answer:**
- **Client-side (HTML5 & JS):** Fast user feedback ke liye (e.g. Price > 0 check, required fields). Lekin ise inspect element ya curl se bypass kiya ja sakta hai.
- **Server-side (PHP):** Real security ke liye. Hamesha server par data sanitize aur validate karna mandatory hota hai kyunki koi bhi request direct API/curl se aa sakti hai.

---

## 🚀 3. How to Run & Demonstrate to the Interviewer

1. Open browser:
   - **Storefront:** `http://localhost/my_shop/index.php`
   - **All Products:** `http://localhost/my_shop/products.php`
   - **Cart:** `http://localhost/my_shop/cart.php`
   - **Product CRUD (Admin):** `http://localhost/my_shop/admin_products.php`
2. **Demo Steps:**
   - **Storefront me Live Search & Filter dikhayein:** Search bar me type karein (e.g. "Watch" ya "Headphones") aur category pills par click karein—bina reload card filter hote hain.
   - **AJAX Add to Cart dikhayein:** Kisi card par "Add" click karein, dekhein badge dynamically increment hota hai aur Toast notification aati hai.
   - **Cart me Quantity adjustment (+/-) aur total calculation dikhayein:** Cart page par jakar quantity change karein, subtotal aur shipping discount check karein.
   - **Product CRUD demonstrate karein:**
     - "Add New Product" par click karke ek naya product banayein.
     - Us product ko edit karein (price ya stock change karein).
     - Us product ko delete karke flash notification dikhayein.
   - **PDO Code Structure dikhayein:** `config/db.php` me PDO connection aur prepared statements interviewer ko point out karein.
