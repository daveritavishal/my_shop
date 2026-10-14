/**
 * Application JavaScript for My Shop
 * Handles: Live Search, Filter, AJAX Cart additions, Quantity changes, Toasts
 */

document.addEventListener('DOMContentLoaded', function () {
    initLiveSearchAndFilter();
    initAjaxAddToCart();
    initCartQuantityControls();
    initQuickViewModal();
});

/**
 * 1. Live Product Search, Category Filter, and Sorting
 */
function initLiveSearchAndFilter() {
    const searchInput = document.getElementById('productSearchInput');
    const categoryPills = document.querySelectorAll('.category-pill');
    const sortSelect = document.getElementById('productSortSelect');
    const productGrid = document.getElementById('productGridContainer');
    const noResultsMsg = document.getElementById('noProductsAlert');

    if (!productGrid) return; // Not on products/index page

    function applyFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const activePill = document.querySelector('.category-pill.active');
        const selectedCategory = activePill ? activePill.getAttribute('data-category').toLowerCase() : 'all';

        const productCards = Array.from(productGrid.querySelectorAll('.product-item-col'));
        let visibleCount = 0;

        productCards.forEach(col => {
            const title = (col.getAttribute('data-name') || '').toLowerCase();
            const category = (col.getAttribute('data-category') || '').toLowerCase();
            const desc = (col.getAttribute('data-desc') || '').toLowerCase();

            const matchesSearch = query === '' || title.includes(query) || desc.includes(query);
            const matchesCategory = selectedCategory === 'all' || category === selectedCategory;

            if (matchesSearch && matchesCategory) {
                col.style.display = '';
                visibleCount++;
            } else {
                col.style.display = 'none';
            }
        });

        if (noResultsMsg) {
            noResultsMsg.classList.toggle('d-none', visibleCount > 0);
        }
    }

    // Search input event
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    // Category pills click
    categoryPills.forEach(pill => {
        pill.addEventListener('click', function (e) {
            e.preventDefault();
            categoryPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    // Sorting
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            const val = this.value;
            const productCards = Array.from(productGrid.querySelectorAll('.product-item-col'));

            productCards.sort((a, b) => {
                const priceA = parseFloat(a.getAttribute('data-price')) || 0;
                const priceB = parseFloat(b.getAttribute('data-price')) || 0;
                const nameA = (a.getAttribute('data-name') || '').toLowerCase();
                const nameB = (b.getAttribute('data-name') || '').toLowerCase();

                if (val === 'price_asc') return priceA - priceB;
                if (val === 'price_desc') return priceB - priceA;
                if (val === 'name_asc') return nameA.localeCompare(nameB);
                return (parseInt(a.getAttribute('data-id')) || 0) - (parseInt(b.getAttribute('data-id')) || 0);
            });

            productCards.forEach(card => productGrid.appendChild(card));
        });
    }
}

/**
 * 2. AJAX Add To Cart with Toast Notification
 */
function initAjaxAddToCart() {
    const addButtons = document.querySelectorAll('.btn-add-to-cart');

    addButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            const qtyInput = document.getElementById('detailQtyInput');
            const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Adding...';

            fetch('cart_action.php?action=add_ajax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    product_id: productId,
                    quantity: qty
                })
            })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                this.innerHTML = originalHtml;

                if (data.status === 'success') {
                    // Update Navbar badge
                    const badge = document.getElementById('navCartBadge');
                    if (badge) {
                        badge.textContent = data.cart_count;
                        badge.classList.remove('d-none');
                    }

                    // Show Toast
                    showToast(data.message || 'Item added to cart!');
                } else {
                    alert(data.message || 'Error adding product to cart.');
                }
            })
            .catch(err => {
                this.disabled = false;
                this.innerHTML = originalHtml;
                console.error(err);
                // Fallback to standard form submission if AJAX fails
                window.location.href = `cart_action.php?action=add&id=${productId}&qty=${qty}`;
            });
        });
    });
}

/**
 * 3. Cart Quantity Controls (+ / -)
 */
function initCartQuantityControls() {
    // Quantity increment/decrement buttons on cart.php
    document.querySelectorAll('.btn-qty-change').forEach(btn => {
        btn.addEventListener('click', function () {
            const form = this.closest('form');
            if (form) form.submit();
        });
    });
}

/**
 * 4. Quick View Modal Handler
 */
function initQuickViewModal() {
    const quickViewModal = document.getElementById('quickViewModal');
    if (!quickViewModal) return;

    quickViewModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const price = button.getAttribute('data-price');
        const category = button.getAttribute('data-category');
        const desc = button.getAttribute('data-desc');
        const image = button.getAttribute('data-image');
        const stock = button.getAttribute('data-stock');

        quickViewModal.querySelector('#modalProductTitle').textContent = name;
        quickViewModal.querySelector('#modalProductPrice').textContent = price;
        quickViewModal.querySelector('#modalProductCategory').textContent = category;
        quickViewModal.querySelector('#modalProductDesc').textContent = desc;
        quickViewModal.querySelector('#modalProductStock').textContent = stock + ' units in stock';
        quickViewModal.querySelector('#modalProductImg').src = image;

        const modalAddBtn = quickViewModal.querySelector('#modalAddToCartBtn');
        if (modalAddBtn) {
            modalAddBtn.setAttribute('data-id', id);
        }

        const modalDetailLink = quickViewModal.querySelector('#modalDetailLink');
        if (modalDetailLink) {
            modalDetailLink.href = 'product_detail.php?id=' + id;
        }
    });
}

/**
 * Helper to display Toast notification
 */
function showToast(message) {
    const toastEl = document.getElementById('liveCartToast');
    const toastMsg = document.getElementById('toastMessage');
    if (toastEl && toastMsg) {
        toastMsg.textContent = message;
        const toast = new bootstrap.Toast(toastEl, { delay: 2800 });
        toast.show();
    }
}
