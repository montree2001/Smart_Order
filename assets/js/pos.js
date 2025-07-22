/**
 * POS System JavaScript
 * Smart Order Management System
 * Optimized for Tablet & Mobile
 */

// Global Variables
let cart = [];
let currentOrder = null;
let settings = {
    autoRefresh: true,
    soundEnabled: true,
    darkMode: false,
    language: 'th-TH',
    voiceSpeed: 0.8,
    voiceVolume: 1.0
};

// Initialize POS System
document.addEventListener('DOMContentLoaded', function() {
    initializePOS();
    loadSettings();
    setupEventListeners();
    startAutoRefresh();
    updateDateTime();
    
    // Update time every second
    setInterval(updateDateTime, 1000);
    
    console.log('POS System initialized successfully');
});

/**
 * Initialize POS System
 */
function initializePOS() {
    // Check if running on tablet/mobile
    detectDevice();
    
    // Load cart from localStorage
    loadCart();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Setup keyboard shortcuts
    setupKeyboardShortcuts();
    
    // Setup touch events for mobile
    setupTouchEvents();
}

/**
 * Detect device type
 */
function detectDevice() {
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTablet = /(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(navigator.userAgent);
    
    if (isMobile || isTablet) {
        document.body.classList.add('mobile-device');
    }
    
    // Add viewport meta tag for mobile
    if (!document.querySelector('meta[name="viewport"]')) {
        const viewport = document.createElement('meta');
        viewport.name = 'viewport';
        viewport.content = 'width=device-width, initial-scale=1.0, user-scalable=no';
        document.head.appendChild(viewport);
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Cart events
    document.addEventListener('click', function(e) {
        if (e.target.matches('.menu-item')) {
            handleMenuItemClick(e.target);
        }
        
        if (e.target.matches('.quantity-btn')) {
            handleQuantityChange(e.target);
        }
        
        if (e.target.matches('.remove-item')) {
            handleRemoveItem(e.target);
        }
    });
    
    // Form events
    const customerForm = document.getElementById('customerForm');
    if (customerForm) {
        customerForm.addEventListener('input', validateForm);
        customerForm.addEventListener('submit', handleOrderSubmit);
    }
    
    // Payment events
    const paymentMethods = document.querySelectorAll('.payment-method');
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            selectPaymentMethod(this.dataset.method);
        });
    });
    
    // Window events
    window.addEventListener('beforeunload', function(e) {
        if (cart.length > 0) {
            e.preventDefault();
            e.returnValue = 'คุณมีรายการในตะกร้า ต้องการออกจากหน้านี้หรือไม่?';
        }
    });
    
    window.addEventListener('online', function() {
        showToast('เชื่อมต่ออินเทอร์เน็ต', 'เชื่อมต่ออินเทอร์เน็ตเรียบร้อยแล้ว', 'success');
    });
    
    window.addEventListener('offline', function() {
        showToast('ไม่มีสัญญาณอินเทอร์เน็ต', 'กรุณาตรวจสอบการเชื่อมต่อ', 'warning');
    });
}

/**
 * Update current date and time
 */
function updateDateTime() {
    const now = new Date();
    const options = {
        year: 'numeric',
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZone: 'Asia/Bangkok'
    };
    
    const timeElements = document.querySelectorAll('#currentTime');
    timeElements.forEach(element => {
        element.textContent = now.toLocaleDateString('th-TH', options);
    });
}

/**
 * Cart Management
 */
function addToCart(itemId, itemData) {
    const existingItem = cart.find(item => item.id == itemId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: itemId,
            name: itemData.name,
            price: parseFloat(itemData.price),
            quantity: 1,
            image: itemData.image || null,
            notes: ''
        });
    }
    
    updateCartDisplay();
    saveCart();
    playSound('add');
    
    // Animation effect
    animateAddToCart(itemId);
}

function removeFromCart(itemId) {
    cart = cart.filter(item => item.id != itemId);
    updateCartDisplay();
    saveCart();
    playSound('remove');
}

function updateQuantity(itemId, newQuantity) {
    const item = cart.find(item => item.id == itemId);
    
    if (item) {
        if (newQuantity <= 0) {
            removeFromCart(itemId);
        } else {
            item.quantity = newQuantity;
            updateCartDisplay();
            saveCart();
        }
    }
}

function clearCart() {
    if (cart.length === 0) {
        showToast('ตะกร้าว่าง', 'ไม่มีรายการในตะกร้า', 'info');
        return;
    }
    
    if (confirm('คุณต้องการล้างรายการทั้งหมดหรือไม่?')) {
        cart = [];
        updateCartDisplay();
        saveCart();
        clearCustomerForm();
        showToast('ล้างตะกร้าสำเร็จ', 'รายการทั้งหมดถูกล้างแล้ว', 'info');
        playSound('clear');
    }
}

function updateCartDisplay() {
    const cartItemsContainer = document.getElementById('cartItems');
    const totalItems = document.getElementById('totalItems');
    const totalPrice = document.getElementById('totalPrice'); 
    const finalPrice = document.getElementById('finalPrice');
    
    if (!cartItemsContainer) return;
    
    // Clear container
    cartItemsContainer.innerHTML = '';
    
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart fa-2x text-muted"></i>
                <p class="text-muted mt-2">ยังไม่มีรายการในตะกร้า</p>
            </div>
        `;
    } else {
        cart.forEach(item => {
            const cartItem = createCartItemElement(item);
            cartItemsContainer.appendChild(cartItem);
        });
    }
    
    // Update totals
    const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    const totalAmount = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    if (totalItems) totalItems.textContent = itemCount;
    if (totalPrice) totalPrice.textContent = `฿${totalAmount.toFixed(0)}`;
    if (finalPrice) finalPrice.textContent = `฿${totalAmount.toFixed(0)}`;
    
    // Update order button state
    updateOrderButtonState();
    
    // Update cart badge in navigation
    updateCartBadge(itemCount);
}

function createCartItemElement(item) {
    const div = document.createElement('div');
    div.className = 'cart-item';
    div.dataset.itemId = item.id;
    
    div.innerHTML = `
        <div class="item-info">
            <h6>${item.name}</h6>
            <div class="item-price">฿${item.price} x ${item.quantity}</div>
        </div>
        <div class="item-controls">
            <div class="quantity-controls">
                <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn" 
                        data-action="decrease" data-item-id="${item.id}">
                    <i class="fas fa-minus"></i>
                </button>
                <span class="quantity">${item.quantity}</span>
                <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn" 
                        data-action="increase" data-item-id="${item.id}">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="item-total">
                <strong>฿${(item.price * item.quantity).toFixed(0)}</strong>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger remove-item" 
                data-item-id="${item.id}" title="ลบรายการ">
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    return div;
}

function saveCart() {
    try {
        localStorage.setItem('posCart', JSON.stringify(cart));
    } catch (e) {
        console.warn('Cannot save cart to localStorage:', e);
    }
}

function loadCart() {
    try {
        const savedCart = localStorage.getItem('posCart');
        if (savedCart) {
            cart = JSON.parse(savedCart);
            updateCartDisplay();
        }
    } catch (e) {
        console.warn('Cannot load cart from localStorage:', e);
        cart = [];
    }
}

/**
 * Event Handlers
 */
function handleMenuItemClick(element) {
    const itemId = element.dataset.itemId;
    const itemName = element.dataset.itemName;
    const itemPrice = element.dataset.itemPrice;
    const itemImage = element.dataset.itemImage;
    const isAvailable = element.dataset.available !== '0';
    
    if (!isAvailable) {
        showToast('สินค้าไม่พร้อม', 'สินค้านี้หมดชั่วคราว', 'warning');
        return;
    }
    
    const itemData = {
        name: itemName,
        price: itemPrice,
        image: itemImage
    };
    
    addToCart(itemId, itemData);
}

function handleQuantityChange(button) {
    const action = button.dataset.action;
    const itemId = button.dataset.itemId;
    const item = cart.find(item => item.id == itemId);
    
    if (!item) return;
    
    const newQuantity = action === 'increase' ? item.quantity + 1 : item.quantity - 1;
    updateQuantity(itemId, newQuantity);
}

function handleRemoveItem(button) {
    const itemId = button.dataset.itemId;
    
    if (confirm('ต้องการลบรายการนี้หรือไม่?')) {
        removeFromCart(itemId);
    }
}

function handleOrderSubmit(e) {
    e.preventDefault();
    
    if (!validateOrder()) {
        return;
    }
    
    const formData = new FormData(e.target);
    const orderData = {
        customer_name: formData.get('customer_name'),
        customer_phone: formData.get('customer_phone'),
        cart: cart,
        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
        notes: formData.get('notes') || ''
    };
    
    // Save order data for payment page
    try {
        sessionStorage.setItem('orderData', JSON.stringify(orderData));
        window.location.href = 'payment.php';
    } catch (e) {
        console.error('Cannot save order data:', e);
        showToast('เกิดข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
    }
}

/**
 * Validation
 */
function validateOrder() {
    const customerName = document.getElementById('customerName')?.value?.trim();
    
    if (!customerName) {
        showToast('ข้อมูลไม่ครบ', 'กรุณากรอกชื่อลูกค้า', 'warning');
        document.getElementById('customerName')?.focus();
        return false;
    }
    
    if (cart.length === 0) {
        showToast('ไม่มีสินค้า', 'กรุณาเลือกสินค้าก่อน', 'warning');
        return false;
    }
    
    return true;
}

function validateForm() {
    const customerName = document.getElementById('customerName')?.value?.trim();
    const hasItems = cart.length > 0;
    
    updateOrderButtonState(customerName && hasItems);
}

function updateOrderButtonState(isValid = null) {
    const orderButton = document.getElementById('processOrderBtn');
    if (!orderButton) return;
    
    if (isValid === null) {
        const customerName = document.getElementById('customerName')?.value?.trim();
        const hasItems = cart.length > 0;
        isValid = customerName && hasItems;
    }
    
    orderButton.disabled = !isValid;
    orderButton.className = isValid ? 
        'btn btn-success btn-lg w-100' : 
        'btn btn-secondary btn-lg w-100';
}

function clearCustomerForm() {
    const customerName = document.getElementById('customerName');
    const customerPhone = document.getElementById('customerPhone');
    const notes = document.getElementById('notes');
    
    if (customerName) customerName.value = '';
    if (customerPhone) customerPhone.value = '';
    if (notes) notes.value = '';
    
    updateOrderButtonState();
}

/**
 * Payment Functions
 */
function selectPaymentMethod(method) {
    // Remove active class from all payment methods
    document.querySelectorAll('.payment-method').forEach(el => {
        el.classList.remove('active');
    });
    
    // Add active class to selected method
    const selectedMethod = document.querySelector(`[data-method="${method}"]`);
    if (selectedMethod) {
        selectedMethod.classList.add('active');
    }
    
    // Show payment details
    showPaymentDetails(method);
    
    // Enable confirm button
    const confirmButton = document.getElementById('confirmPaymentBtn');
    if (confirmButton) {
        confirmButton.disabled = false;
    }
    
    playSound('select');
}

function showPaymentDetails(method) {
    // Hide all payment details
    document.querySelectorAll('[id$="Payment"]').forEach(el => {
        el.style.display = 'none';
    });
    
    // Show payment details container
    const paymentDetails = document.getElementById('paymentDetails');
    if (paymentDetails) {
        paymentDetails.style.display = 'block';
    }
    
    // Show selected method details
    const methodDetails = document.getElementById(method + 'Payment');
    if (methodDetails) {
        methodDetails.style.display = 'block';
        
        // Initialize method-specific functionality
        initializePaymentMethod(method);
    }
}

function initializePaymentMethod(method) {
    switch (method) {
        case 'cash':
            initializeCashPayment();
            break;
        case 'qr':
            initializeQRPayment();
            break;
        case 'card':
            initializeCardPayment();
            break;
        case 'transfer':
            initializeTransferPayment();
            break;
    }
}

function initializeCashPayment() {
    const totalAmount = getTotalAmount();
    const totalAmountInput = document.getElementById('totalAmount');
    
    if (totalAmountInput) {
        totalAmountInput.value = `฿${totalAmount}`;
    }
    
    // Generate quick amount buttons
    generateQuickAmountButtons(totalAmount);
    
    // Setup change calculation
    const receivedAmountInput = document.getElementById('receivedAmount');
    if (receivedAmountInput) {
        receivedAmountInput.addEventListener('input', calculateChange);
        receivedAmountInput.focus();
    }
}

function generateQuickAmountButtons(totalAmount) {
    const container = document.getElementById('quickAmounts');
    if (!container) return;
    
    const amounts = [
        Math.ceil(totalAmount / 100) * 100,
        Math.ceil(totalAmount / 500) * 500,
        Math.ceil(totalAmount / 1000) * 1000
    ];
    
    // Add common amounts
    amounts.push(500, 1000, 2000);
    
    // Remove duplicates and sort
    const uniqueAmounts = [...new Set(amounts)]
        .filter(amount => amount >= totalAmount)
        .sort((a, b) => a - b)
        .slice(0, 6);
    
    container.innerHTML = uniqueAmounts.map(amount => `
        <button type="button" class="btn btn-outline-primary quick-amount" 
                onclick="setReceivedAmount(${amount})">
            ฿${amount}
        </button>
    `).join('');
}

function setReceivedAmount(amount) {
    const receivedAmountInput = document.getElementById('receivedAmount');
    if (receivedAmountInput) {
        receivedAmountInput.value = amount;
        calculateChange();
    }
}

function calculateChange() {
    const totalAmount = getTotalAmount();
    const receivedAmount = parseFloat(document.getElementById('receivedAmount')?.value) || 0;
    const change = Math.max(0, receivedAmount - totalAmount);
    
    const changeElement = document.getElementById('changeAmount');
    if (changeElement) {
        changeElement.textContent = `฿${change.toFixed(0)}`;
    }
}

function initializeQRPayment() {
    const totalAmount = getTotalAmount();
    generateQRCode(totalAmount);
}

function generateQRCode(amount) {
    const container = document.getElementById('qrCodeContainer');
    if (!container) return;
    
    // Show loading
    container.innerHTML = '<div class="loading-spinner"></div><p>กำลังสร้าง QR Code...</p>';
    
    // Simulate QR code generation
    setTimeout(() => {
        container.innerHTML = `
            <div class="qr-code-placeholder" style="width: 250px; height: 250px; background: #f0f0f0; 
                 border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; 
                 margin: 0 auto; border-radius: 10px;">
                <div class="text-center">
                    <i class="fas fa-qrcode fa-3x text-muted"></i>
                    <div class="mt-2">QR Code</div>
                    <div class="mt-1"><strong>฿${amount}</strong></div>
                </div>
            </div>
            <p class="mt-3"><strong>จำนวนเงิน: ฿${amount}</strong></p>
            <p class="text-muted">สแกน QR Code ด้วยแอปธนาคาร</p>
        `;
    }, 1500);
}

function initializeCardPayment() {
    const container = document.querySelector('.card-payment-status');
    if (!container) return;
    
    // Simulate card reader connection
    setTimeout(() => {
        container.innerHTML = `
            <i class="fas fa-check-circle fa-3x text-success"></i>
            <h5 class="mt-3">พร้อมรับชำระ</h5>
            <p class="text-muted">เครื่อง EDC พร้อมใช้งาน</p>
        `;
    }, 2000);
}

function initializeTransferPayment() {
    const totalAmount = getTotalAmount();
    const transferAmountElement = document.getElementById('transferAmount');
    
    if (transferAmountElement) {
        transferAmountElement.textContent = `฿${totalAmount}`;
    }
}

function getTotalAmount() {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
}

/**
 * Audio Functions
 */
function playSound(type) {
    if (!settings.soundEnabled) return;
    
    const audioContext = window.AudioContext || window.webkitAudioContext;
    if (!audioContext) return;
    
    try {
        const context = new audioContext();
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        
        oscillator.connect(gain);
        gain.connect(context.destination);
        
        // Different sounds for different actions
        const sounds = {
            add: { frequency: 800, duration: 0.1 },
            remove: { frequency: 400, duration: 0.15 },
            clear: { frequency: 300, duration: 0.2 },
            select: { frequency: 600, duration: 0.05 },
            success: { frequency: 1000, duration: 0.3 },
            error: { frequency: 200, duration: 0.5 }
        };
        
        const sound = sounds[type] || sounds.select;
        
        oscillator.frequency.setValueAtTime(sound.frequency, context.currentTime);
        gain.gain.setValueAtTime(0.1, context.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + sound.duration);
        
        oscillator.start(context.currentTime);
        oscillator.stop(context.currentTime + sound.duration);
        
    } catch (e) {
        console.warn('Cannot play sound:', e);
    }
}

function playVoiceAnnouncement(text) {
    if (!settings.soundEnabled) return;
    
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = settings.language;
        utterance.rate = settings.voiceSpeed;
        utterance.volume = settings.voiceVolume;
        utterance.pitch = 1.2;
        
        speechSynthesis.speak(utterance);
    }
}

/**
 * Animation Functions
 */
function animateAddToCart(itemId) {
    const menuItem = document.querySelector(`[data-item-id="${itemId}"]`);
    const cartIcon = document.querySelector('.fa-shopping-cart');
    
    if (!menuItem || !cartIcon) return;
    
    // Add pulse animation to menu item
    menuItem.style.transform = 'scale(1.1)';
    menuItem.style.transition = 'transform 0.2s ease';
    
    setTimeout(() => {
        menuItem.style.transform = '';
    }, 200);
    
    // Add bounce animation to cart icon
    if (cartIcon) {
        cartIcon.style.transform = 'scale(1.3)';
        cartIcon.style.transition = 'transform 0.3s ease';
        
        setTimeout(() => {
            cartIcon.style.transform = '';
        }, 300);
    }
}

function updateCartBadge(count) {
    const badge = document.querySelector('.cart-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline' : 'none';
    }
}

/**
 * Utility Functions
 */
function showToast(title, message, type = 'info') {
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    
    const typeClasses = {
        success: 'text-white bg-success',
        error: 'text-white bg-danger', 
        warning: 'text-white bg-warning',
        info: 'text-white bg-primary'
    };
    
    toast.className = `toast align-items-center ${typeClasses[type] || typeClasses.info} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Add to container
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    container.appendChild(toast);
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: type === 'error' ? 5000 : 3000
    });
    bsToast.show();
    
    // Play sound
    playSound(type === 'error' ? 'error' : 'success');
    
    // Remove from DOM after hiding
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDateTime(date) {
    return new Intl.DateTimeFormat('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

/**
 * Settings Management
 */
function loadSettings() {
    try {
        const savedSettings = localStorage.getItem('posSettings');
        if (savedSettings) {
            settings = { ...settings, ...JSON.parse(savedSettings) };
        }
        applySettings();
    } catch (e) {
        console.warn('Cannot load settings:', e);
    }
}

function saveSettings() {
    try {
        localStorage.setItem('posSettings', JSON.stringify(settings));
    } catch (e) {
        console.warn('Cannot save settings:', e);
    }
}

function applySettings() {
    // Apply dark mode
    if (settings.darkMode) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }
    
    // Apply other settings as needed
    document.documentElement.style.setProperty('--voice-speed', settings.voiceSpeed);
    document.documentElement.style.setProperty('--voice-volume', settings.voiceVolume);
}

/**
 * Auto Refresh Functions
 */
let autoRefreshInterval;

function startAutoRefresh() {
    if (!settings.autoRefresh) return;
    
    // Refresh every 30 seconds
    autoRefreshInterval = setInterval(() => {
        refreshData();
    }, 30000);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function refreshData() {
    // Refresh dashboard data, queue status, etc.
    const currentPage = window.location.pathname.split('/').pop();
    
    switch (currentPage) {
        case 'index.php':
            refreshDashboard();
            break;
        case 'queue_display.php':
            refreshQueueDisplay();
            break;
        case 'order_list.php':
            refreshOrderList();
            break;
    }
}

function refreshDashboard() {
    fetch('api/dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data.data);
            }
        })
        .catch(error => console.warn('Failed to refresh dashboard:', error));
}

function refreshQueueDisplay() {
    fetch('queue_display.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_queue_data'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateQueueDisplay(data.data);
        }
    })
    .catch(error => console.warn('Failed to refresh queue:', error));
}

function refreshOrderList() {
    // Reload the order list table
    const table = document.getElementById('ordersTable');
    if (table && $.fn.DataTable.isDataTable(table)) {
        $(table).DataTable().ajax.reload(null, false);
    }
}

/**
 * Keyboard Shortcuts
 */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Alt + key shortcuts
        if (e.altKey) {
            switch (e.key) {
                case '1':
                    e.preventDefault();
                    window.location.href = 'index.php';
                    break;
                case '2':
                    e.preventDefault(); 
                    window.location.href = 'new_order.php';
                    break;
                case '3':
                    e.preventDefault();
                    window.location.href = 'order_list.php';
                    break;
                case '4':
                    e.preventDefault();
                    window.location.href = 'queue_display.php';
                    break;
                case 'c':
                    e.preventDefault();
                    clearCart();
                    break;
            }
        }
        
        // Escape key
        if (e.key === 'Escape') {
            // Close modals or cancel operations
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                const bsModal = bootstrap.Modal.getInstance(openModal);
                if (bsModal) bsModal.hide();
            }
        }
        
        // Enter key for form submission
        if (e.key === 'Enter' && e.ctrlKey) {
            const activeElement = document.activeElement;
            if (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA') {
                const form = activeElement.closest('form');
                if (form) {
                    form.submit();
                }
            }
        }
    });
}

/**
 * Touch Events for Mobile
 */
function setupTouchEvents() {
    let touchStartX = 0;
    let touchEndX = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 100;
        const swipeDistance = touchEndX - touchStartX;
        
        if (Math.abs(swipeDistance) > swipeThreshold) {
            if (swipeDistance > 0) {
                // Swipe right - could navigate back
                handleSwipeRight();
            } else {
                // Swipe left - could navigate forward  
                handleSwipeLeft();
            }
        }
    }
    
    function handleSwipeRight() {
        // Implement swipe right logic
        console.log('Swipe right detected');
    }
    
    function handleSwipeLeft() {
        // Implement swipe left logic
        console.log('Swipe left detected');
    }
}

/**
 * Initialize Tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Network Status Functions
 */
function checkNetworkStatus() {
    return navigator.onLine;
}

function handleOfflineMode() {
    if (!checkNetworkStatus()) {
        showToast('โหมดออฟไลน์', 'ระบบทำงานในโหมดออฟไลน์', 'warning');
        
        // Disable certain features that require network
        const networkElements = document.querySelectorAll('[data-require-network]');
        networkElements.forEach(el => el.disabled = true);
    }
}

/**
 * Error Handling
 */
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    
    // Don't show error toast for every error, only critical ones
    if (e.error && e.error.message && e.error.message.includes('network')) {
        showToast('เกิดข้อผิดพลาด', 'ปัญหาการเชื่อมต่อเครือข่าย', 'error');
    }
});

// Export functions for global use
window.posSystem = {
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    selectPaymentMethod,
    showToast,
    playSound,
    playVoiceAnnouncement,
    loadSettings,
    saveSettings,
    refreshData
};

console.log('POS JavaScript loaded successfully');