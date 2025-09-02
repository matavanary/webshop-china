/**
 * Main JavaScript functionality for Chinese E-commerce Website
 */

// Global app object
window.WebshopChina = {
    config: {
        baseUrl: window.location.origin,
        apiUrl: window.location.origin + '/api',
        currency: 'THB'
    },
    
    // Initialize application
    init: function() {
        this.setupEventListeners();
        this.initializeComponents();
        this.loadCart();
    },
    
    // Setup global event listeners
    setupEventListeners: function() {
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
        
        // Search functionality
        const searchForm = document.getElementById('search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', this.handleSearch);
        }
        
        // Quick view buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('quick-view-btn')) {
                e.preventDefault();
                const productId = e.target.dataset.productId;
                WebshopChina.showQuickView(productId);
            }
        });
        
        // Add to cart buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-to-cart-btn')) {
                e.preventDefault();
                const productId = e.target.dataset.productId;
                const variantId = e.target.dataset.variantId || null;
                const quantity = parseInt(e.target.dataset.quantity) || 1;
                WebshopChina.addToCart(productId, variantId, quantity);
            }
        });
        
        // Close modal on background click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-backdrop')) {
                WebshopChina.closeModal();
            }
        });
        
        // ESC key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                WebshopChina.closeModal();
            }
        });
    },
    
    // Initialize components
    initializeComponents: function() {
        // Initialize image galleries
        this.initImageGalleries();
        
        // Initialize product filters
        this.initProductFilters();
        
        // Initialize quantity selectors
        this.initQuantitySelectors();
    },
    
    // Initialize image galleries
    initImageGalleries: function() {
        const galleries = document.querySelectorAll('.image-gallery');
        galleries.forEach(function(gallery) {
            const images = gallery.querySelectorAll('img');
            images.forEach(function(img, index) {
                img.addEventListener('click', function() {
                    WebshopChina.showImageModal(gallery, index);
                });
            });
        });
    },
    
    // Initialize product filters
    initProductFilters: function() {
        const sortSelect = document.getElementById('sort-select');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                const url = new URL(window.location);
                url.searchParams.set('sort', this.value);
                window.location.href = url.toString();
            });
        }
        
        const categoryFilters = document.querySelectorAll('.category-filter');
        categoryFilters.forEach(function(filter) {
            filter.addEventListener('change', function() {
                const url = new URL(window.location);
                if (this.checked) {
                    url.searchParams.set('category', this.value);
                } else {
                    url.searchParams.delete('category');
                }
                window.location.href = url.toString();
            });
        });
    },
    
    // Initialize quantity selectors
    initQuantitySelectors: function() {
        const quantityInputs = document.querySelectorAll('.quantity-input');
        quantityInputs.forEach(function(input) {
            const minusBtn = input.parentElement.querySelector('.quantity-minus');
            const plusBtn = input.parentElement.querySelector('.quantity-plus');
            
            if (minusBtn) {
                minusBtn.addEventListener('click', function() {
                    const currentValue = parseInt(input.value) || 1;
                    if (currentValue > 1) {
                        input.value = currentValue - 1;
                        input.dispatchEvent(new Event('change'));
                    }
                });
            }
            
            if (plusBtn) {
                plusBtn.addEventListener('click', function() {
                    const currentValue = parseInt(input.value) || 1;
                    const maxValue = parseInt(input.getAttribute('max')) || 999;
                    if (currentValue < maxValue) {
                        input.value = currentValue + 1;
                        input.dispatchEvent(new Event('change'));
                    }
                });
            }
        });
    },
    
    // Handle search
    handleSearch: function(e) {
        e.preventDefault();
        const searchInput = document.getElementById('search-input');
        const query = searchInput.value.trim();
        
        if (query.length < 2) {
            WebshopChina.showAlert('กรุณาใส่คำค้นหาอย่างน้อย 2 ตัวอักษร', 'warning');
            return;
        }
        
        window.location.href = '/search?q=' + encodeURIComponent(query);
    },
    
    // Show quick view modal
    showQuickView: function(productId) {
        this.showLoading();
        
        fetch(this.config.apiUrl + '/product/' + productId + '/quick-view')
            .then(response => response.json())
            .then(data => {
                this.hideLoading();
                if (data.success) {
                    this.displayQuickViewModal(data.product);
                } else {
                    this.showAlert('ไม่สามารถโหลดข้อมูลสินค้าได้', 'error');
                }
            })
            .catch(error => {
                this.hideLoading();
                this.showAlert('เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
            });
    },
    
    // Display quick view modal
    displayQuickViewModal: function(product) {
        const modalHtml = `
            <div class="modal-backdrop fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg max-w-4xl w-full max-h-full overflow-auto">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-bold">${product.name}</h2>
                            <button onclick="WebshopChina.closeModal()" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <img src="${product.images?.[0] || '/assets/images/placeholder.jpg'}" alt="${product.name}" class="w-full h-64 object-cover rounded-lg">
                            </div>
                            <div>
                                <p class="text-gray-600 mb-4">${product.short_description || ''}</p>
                                <div class="mb-4">
                                    <span class="text-2xl font-bold text-red-600">฿${this.formatPrice(product.sale_price || product.base_price)}</span>
                                    ${product.sale_price ? `<span class="text-gray-500 line-through ml-2">฿${this.formatPrice(product.base_price)}</span>` : ''}
                                </div>
                                <button onclick="WebshopChina.addToCart(${product.id})" class="btn-primary w-full">
                                    เพิ่มลงตะกร้า
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    },
    
    // Add to cart
    addToCart: function(productId, variantId = null, quantity = 1) {
        // First check stock
        const stockData = {
            product_id: productId,
            variant_id: variantId,
            quantity: quantity
        };
        
        fetch(this.config.apiUrl + '/product/check-stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(stockData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.in_stock) {
                this.addToCartLocal(productId, variantId, quantity);
                this.showAlert('เพิ่มสินค้าลงตะกร้าแล้ว', 'success');
                this.updateCartDisplay();
            } else {
                this.showAlert('สินค้าไม่เพียงพอในสต็อก', 'warning');
            }
        })
        .catch(error => {
            this.showAlert('เกิดข้อผิดพลาดในการเพิ่มสินค้า', 'error');
        });
    },
    
    // Add to cart locally (localStorage)
    addToCartLocal: function(productId, variantId, quantity) {
        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        
        const existingItem = cart.find(item => 
            item.product_id === productId && item.variant_id === variantId
        );
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            cart.push({
                product_id: productId,
                variant_id: variantId,
                quantity: quantity,
                added_at: new Date().toISOString()
            });
        }
        
        localStorage.setItem('cart', JSON.stringify(cart));
    },
    
    // Load cart from localStorage
    loadCart: function() {
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');
        this.updateCartDisplay(cart);
    },
    
    // Update cart display
    updateCartDisplay: function(cart = null) {
        if (cart === null) {
            cart = JSON.parse(localStorage.getItem('cart') || '[]');
        }
        
        const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
        const cartCountElements = document.querySelectorAll('.cart-count');
        
        cartCountElements.forEach(element => {
            element.textContent = cartCount;
            element.style.display = cartCount > 0 ? 'inline' : 'none';
        });
    },
    
    // Show alert message
    showAlert: function(message, type = 'info') {
        const alertTypes = {
            success: 'alert-success',
            error: 'alert-error',
            warning: 'alert-warning',
            info: 'alert-info'
        };
        
        const alertHtml = `
            <div class="fixed top-4 right-4 z-50 ${alertTypes[type]} max-w-sm">
                <div class="flex items-center">
                    <span class="flex-1">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.fixed.top-4.right-4');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    },
    
    // Show loading indicator
    showLoading: function() {
        const loadingHtml = `
            <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                <div class="loading-spinner"></div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', loadingHtml);
    },
    
    // Hide loading indicator
    hideLoading: function() {
        const loading = document.getElementById('loading-overlay');
        if (loading) {
            loading.remove();
        }
    },
    
    // Close modal
    closeModal: function() {
        const modals = document.querySelectorAll('.modal-backdrop');
        modals.forEach(modal => modal.remove());
    },
    
    // Format price
    formatPrice: function(price) {
        return new Intl.NumberFormat('th-TH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(price);
    },
    
    // Show image modal
    showImageModal: function(gallery, startIndex) {
        const images = Array.from(gallery.querySelectorAll('img'));
        let currentIndex = startIndex;
        
        const modalHtml = `
            <div class="modal-backdrop fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center">
                <div class="relative max-w-4xl max-h-full">
                    <img id="modal-image" src="${images[currentIndex].src}" alt="" class="max-w-full max-h-full object-contain">
                    <button onclick="WebshopChina.closeModal()" class="absolute top-4 right-4 text-white hover:text-gray-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    ${images.length > 1 ? `
                        <button id="prev-image" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button id="next-image" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        if (images.length > 1) {
            document.getElementById('prev-image').addEventListener('click', function() {
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                document.getElementById('modal-image').src = images[currentIndex].src;
            });
            
            document.getElementById('next-image').addEventListener('click', function() {
                currentIndex = (currentIndex + 1) % images.length;
                document.getElementById('modal-image').src = images[currentIndex].src;
            });
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    WebshopChina.init();
});