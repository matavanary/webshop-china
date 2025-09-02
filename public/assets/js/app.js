/**
 * WebShop China - Main JavaScript File
 * 
 * This file contains the main JavaScript functionality for the webshop.
 */

// WebShop China App
class WebShopApp {
    constructor() {
        this.cart = this.loadCart();
        this.init();
    }

    init() {
        // Initialize components when DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initComponents());
        } else {
            this.initComponents();
        }
    }

    initComponents() {
        this.updateCartCount();
        this.initAddToCartButtons();
        this.initSearchFunctionality();
        this.initMobileMenu();
        this.initToastNotifications();
        this.initProductGallery();
        this.initQuantitySelectors();
    }

    // Cart Management
    loadCart() {
        const saved = localStorage.getItem('webshop_cart');
        return saved ? JSON.parse(saved) : [];
    }

    saveCart() {
        localStorage.setItem('webshop_cart', JSON.stringify(this.cart));
        this.updateCartCount();
    }

    addToCart(productId, quantity = 1, productName = '', price = 0) {
        const existingItem = this.cart.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.cart.push({
                id: productId,
                name: productName,
                price: price,
                quantity: quantity,
                addedAt: new Date().toISOString()
            });
        }
        
        this.saveCart();
        this.showToast(`已添加 ${productName} 到购物车`, 'success');
    }

    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.saveCart();
        this.showToast('商品已从购物车移除', 'info');
    }

    updateCartQuantity(productId, quantity) {
        const item = this.cart.find(item => item.id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeFromCart(productId);
            } else {
                item.quantity = quantity;
                this.saveCart();
            }
        }
    }

    getCartTotal() {
        return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    getCartItemCount() {
        return this.cart.reduce((count, item) => count + item.quantity, 0);
    }

    updateCartCount() {
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = this.getCartItemCount();
        }
    }

    // Initialize Add to Cart Buttons
    initAddToCartButtons() {
        document.querySelectorAll('[data-add-to-cart]').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = button.dataset.productId;
                const productName = button.dataset.productName || '商品';
                const price = parseFloat(button.dataset.price) || 0;
                const quantity = parseInt(button.dataset.quantity) || 1;
                
                this.addToCart(productId, quantity, productName, price);
            });
        });
    }

    // Search Functionality
    initSearchFunctionality() {
        const searchInput = document.querySelector('input[type="text"][placeholder*="搜索"]');
        if (!searchInput) return;

        let searchTimeout;
        const suggestionsContainer = this.createSearchSuggestions(searchInput);

        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();

            if (query.length < 2) {
                this.hideSearchSuggestions(suggestionsContainer);
                return;
            }

            searchTimeout = setTimeout(() => {
                this.searchProducts(query, suggestionsContainer);
            }, 300);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch(e.target.value);
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                this.hideSearchSuggestions(suggestionsContainer);
            }
        });
    }

    createSearchSuggestions(searchInput) {
        const container = document.createElement('div');
        container.className = 'search-suggestions hidden';
        searchInput.parentElement.appendChild(container);
        return container;
    }

    searchProducts(query, container) {
        // Mock search results - in a real app, this would make an API call
        const mockResults = [
            { id: 1, name: `${query} 相关商品 1`, price: 99.99 },
            { id: 2, name: `${query} 相关商品 2`, price: 149.99 },
            { id: 3, name: `${query} 相关商品 3`, price: 199.99 },
        ];

        this.showSearchSuggestions(container, mockResults, query);
    }

    showSearchSuggestions(container, results, query) {
        container.innerHTML = '';
        
        if (results.length === 0) {
            container.innerHTML = '<div class="search-suggestion text-gray-500">没有找到相关商品</div>';
        } else {
            results.forEach(result => {
                const item = document.createElement('div');
                item.className = 'search-suggestion';
                item.innerHTML = `
                    <div class="flex justify-between items-center">
                        <span>${result.name}</span>
                        <span class="text-blue-600 font-medium">￥${result.price}</span>
                    </div>
                `;
                item.addEventListener('click', () => {
                    window.location.href = `/product?id=${result.id}`;
                });
                container.appendChild(item);
            });
        }

        container.classList.remove('hidden');
    }

    hideSearchSuggestions(container) {
        container.classList.add('hidden');
    }

    performSearch(query) {
        if (query.trim()) {
            window.location.href = `/products?search=${encodeURIComponent(query)}`;
        }
    }

    // Mobile Menu
    initMobileMenu() {
        const mobileMenuButton = document.querySelector('[data-mobile-menu-toggle]');
        const mobileMenu = document.querySelector('[data-mobile-menu]');

        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!mobileMenuButton.contains(e.target) && !mobileMenu.contains(e.target)) {
                    mobileMenu.classList.add('hidden');
                }
            });
        }
    }

    // Toast Notifications
    initToastNotifications() {
        // Create toast container if it doesn't exist
        if (!document.querySelector('.toast-container')) {
            const container = document.createElement('div');
            container.className = 'toast-container fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(container);
        }
    }

    showToast(message, type = 'info', duration = 3000) {
        const container = document.querySelector('.toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type} max-w-sm bg-white border rounded-lg shadow-lg p-4`;
        toast.innerHTML = `
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">${message}</p>
                </div>
                <button class="ml-4 text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

        container.appendChild(toast);

        // Animate in
        setTimeout(() => toast.classList.add('show'), 100);

        // Auto remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // Product Gallery
    initProductGallery() {
        const gallery = document.querySelector('[data-product-gallery]');
        if (!gallery) return;

        const mainImage = gallery.querySelector('[data-main-image]');
        const thumbnails = gallery.querySelectorAll('[data-thumbnail]');

        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', () => {
                // Update main image
                const imageSrc = thumbnail.dataset.imageSrc;
                if (mainImage && imageSrc) {
                    mainImage.src = imageSrc;
                }

                // Update active thumbnail
                thumbnails.forEach(t => t.classList.remove('active'));
                thumbnail.classList.add('active');
            });
        });
    }

    // Quantity Selectors
    initQuantitySelectors() {
        document.querySelectorAll('[data-quantity-selector]').forEach(selector => {
            const minusBtn = selector.querySelector('[data-quantity-minus]');
            const plusBtn = selector.querySelector('[data-quantity-plus]');
            const input = selector.querySelector('[data-quantity-input]');

            if (minusBtn && plusBtn && input) {
                minusBtn.addEventListener('click', () => {
                    const currentValue = parseInt(input.value) || 1;
                    const newValue = Math.max(1, currentValue - 1);
                    input.value = newValue;
                    this.triggerQuantityChange(selector, newValue);
                });

                plusBtn.addEventListener('click', () => {
                    const currentValue = parseInt(input.value) || 1;
                    const maxValue = parseInt(input.dataset.max) || 999;
                    const newValue = Math.min(maxValue, currentValue + 1);
                    input.value = newValue;
                    this.triggerQuantityChange(selector, newValue);
                });

                input.addEventListener('change', () => {
                    const value = parseInt(input.value) || 1;
                    const minValue = parseInt(input.dataset.min) || 1;
                    const maxValue = parseInt(input.dataset.max) || 999;
                    const newValue = Math.min(Math.max(minValue, value), maxValue);
                    input.value = newValue;
                    this.triggerQuantityChange(selector, newValue);
                });
            }
        });
    }

    triggerQuantityChange(selector, newValue) {
        const productId = selector.dataset.productId;
        if (productId) {
            // If this is a cart item, update the cart
            if (selector.dataset.cartItem === 'true') {
                this.updateCartQuantity(productId, newValue);
            }
        }

        // Trigger custom event
        selector.dispatchEvent(new CustomEvent('quantityChanged', {
            detail: { productId, quantity: newValue }
        }));
    }

    // Utility Methods
    formatPrice(price) {
        return new Intl.NumberFormat('zh-CN', {
            style: 'currency',
            currency: 'CNY'
        }).format(price);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // API Helper
    async apiRequest(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            this.showToast('网络请求失败，请稍后重试', 'error');
            throw error;
        }
    }
}

// Initialize the app
const webshopApp = new WebShopApp();

// Global utility functions
window.WebShop = {
    addToCart: (productId, quantity, name, price) => webshopApp.addToCart(productId, quantity, name, price),
    removeFromCart: (productId) => webshopApp.removeFromCart(productId),
    showToast: (message, type, duration) => webshopApp.showToast(message, type, duration),
    formatPrice: (price) => webshopApp.formatPrice(price)
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebShopApp;
}