/**
 * Abandoned Cart Tracking System
 * Tracks user behavior in cart and detects abandonment
 */

class AbandonedCartTracker {
    constructor() {
        this.sessionStartTime = Date.now();
        this.lastActivity = Date.now();
        this.inactivityThreshold = 30 * 60 * 1000; // 30 minutes
        this.pageVisitThreshold = 5 * 60 * 1000; // 5 minutes on cart page
        this.cartPageTimer = null;
        this.inactivityTimer = null;
        this.hasShownAbandonmentSurvey = false;
        this.cartActions = [];
        this.pagesVisited = [];
        
        this.init();
    }
    
    init() {
        // Track session start
        this.trackSessionStart();
        
        // Track cart page visits
        if (this.isCartPage()) {
            this.startCartPageTimer();
        }
        
        // Track user activity
        this.setupActivityTracking();
        
        // Track page visibility changes
        this.setupVisibilityTracking();
        
        // Track beforeunload events
        this.setupBeforeUnloadTracking();
        
        // Periodically update session activity
        this.startActivityUpdater();
    }
    
    isCartPage() {
        return window.location.pathname.includes('/cart/') || 
               window.location.pathname.includes('cart.php');
    }
    
    isCheckoutPage() {
        return window.location.pathname.includes('/checkout/') || 
               window.location.pathname.includes('checkout.php');
    }
    
    trackSessionStart() {
        const sessionData = {
            action: 'session_start',
            timestamp: new Date().toISOString(),
            page: window.location.pathname,
            referrer: document.referrer,
            user_agent: navigator.userAgent
        };
        
        this.sendToServer('/api/track_cart_session.php', sessionData);
    }
    
    startCartPageTimer() {
        this.cartPageTimer = setTimeout(() => {
            if (this.isCartPage() && !this.isCheckoutPage()) {
                this.trackPotentialAbandonment('time_threshold');
            }
        }, this.pageVisitThreshold);
    }
    
    setupActivityTracking() {
        const activityEvents = ['click', 'scroll', 'keypress', 'mousemove'];
        
        activityEvents.forEach(event => {
            document.addEventListener(event, () => {
                this.updateLastActivity();
            }, { passive: true });
        });
        
        // Track cart-specific actions
        this.trackCartActions();
    }
    
    trackCartActions() {
        // Track add to cart
        document.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart-btn') || 
                e.target.closest('[onclick*="addToCart"]')) {
                this.logCartAction('add_to_cart', {
                    item_id: e.target.dataset.itemId,
                    item_type: e.target.dataset.itemType
                });
            }
            
            // Track remove from cart
            if (e.target.closest('.remove-item-btn') ||
                e.target.closest('[onclick*="removeItem"]')) {
                this.logCartAction('remove_from_cart', {
                    cart_id: e.target.dataset.cartId
                });
            }
            
            // Track quantity updates
            if (e.target.closest('.update-quantity-btn')) {
                this.logCartAction('update_quantity', {
                    cart_id: e.target.dataset.cartId,
                    new_quantity: e.target.dataset.quantity
                });
            }
            
            // Track checkout button clicks
            if (e.target.closest('.btn-checkout') || 
                e.target.closest('#checkout-btn')) {
                this.logCartAction('checkout_attempted');
                this.clearTimers(); // Don't track as abandonment if checkout started
            }
        });
        
        // Track quantity changes via input fields
        document.addEventListener('change', (e) => {
            if (e.target.name === 'quantity' && e.target.closest('.cart-item')) {
                this.logCartAction('quantity_changed', {
                    cart_id: e.target.dataset.cartId,
                    new_quantity: e.target.value
                });
            }
        });
    }
    
    logCartAction(action, data = {}) {
        const actionData = {
            action,
            timestamp: new Date().toISOString(),
            data,
            page: window.location.pathname
        };
        
        this.cartActions.push(actionData);
        this.updateLastActivity();
        
        // Send to server
        this.sendToServer('/api/track_cart_action.php', actionData);
    }
    
    setupVisibilityTracking() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.onPageHidden();
            } else {
                this.onPageVisible();
            }
        });
    }
    
    setupBeforeUnloadTracking() {
        window.addEventListener('beforeunload', (e) => {
            if (this.shouldTrackAbandonment()) {
                this.trackAbandonment('page_exit');
            }
        });
        
        // Track navigation away from cart
        window.addEventListener('unload', () => {
            if (this.shouldTrackAbandonment()) {
                this.trackAbandonment('navigation_away');
            }
        });
    }
    
    onPageHidden() {
        if (this.shouldTrackAbandonment()) {
            // Start a timer for potential abandonment
            this.inactivityTimer = setTimeout(() => {
                this.trackPotentialAbandonment('inactivity');
            }, this.inactivityThreshold);
        }
    }
    
    onPageVisible() {
        // Clear inactivity timer when user returns
        if (this.inactivityTimer) {
            clearTimeout(this.inactivityTimer);
            this.inactivityTimer = null;
        }
        this.updateLastActivity();
    }
    
    shouldTrackAbandonment() {
        return this.isCartPage() && 
               !this.isCheckoutPage() && 
               this.hasItemsInCart() &&
               !this.hasShownAbandonmentSurvey;
    }
    
    hasItemsInCart() {
        // Check if there are items in the cart
        const cartItems = document.querySelectorAll('.cart-item');
        const cartBadge = document.querySelector('.cart-badge');
        
        return (cartItems && cartItems.length > 0) || 
               (cartBadge && parseInt(cartBadge.textContent) > 0);
    }
    
    trackPotentialAbandonment(trigger) {
        if (!this.shouldTrackAbandonment()) return;
        
        // Show abandonment survey before tracking
        this.showAbandonmentSurvey(trigger);
    }
    
    trackAbandonment(trigger, reason = null) {
        const abandonmentData = {
            action: 'cart_abandoned',
            trigger: trigger,
            session_duration: Date.now() - this.sessionStartTime,
            last_activity: this.lastActivity,
            cart_actions: this.cartActions,
            pages_visited: this.pagesVisited,
            reason: reason,
            timestamp: new Date().toISOString(),
            page: window.location.pathname
        };
        
        this.sendToServer('/api/track_abandonment.php', abandonmentData);
        this.hasShownAbandonmentSurvey = true;
        this.clearTimers();
    }
    
    showAbandonmentSurvey(trigger) {
        if (this.hasShownAbandonmentSurvey) return;
        
        // Create and show abandonment survey modal
        const modal = this.createAbandonmentSurveyModal();
        document.body.appendChild(modal);
        
        // Show modal after a short delay
        setTimeout(() => {
            modal.classList.add('show');
        }, 500);
        
        this.hasShownAbandonmentSurvey = true;
    }
    
    createAbandonmentSurveyModal() {
        const modal = document.createElement('div');
        modal.className = 'abandonment-survey-modal';
        modal.innerHTML = `
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Tunggu sebentar! 🛒</h3>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Kami melihat Anda akan meninggalkan keranjang belanja. Boleh kami tahu alasannya?</p>
                    <div class="reason-options">
                        <label><input type="radio" name="abandon_reason" value="price_too_high"> Harga terlalu mahal</label>
                        <label><input type="radio" name="abandon_reason" value="shipping_cost"> Biaya pengiriman terlalu tinggi</label>
                        <label><input type="radio" name="abandon_reason" value="not_sure"> Masih belum yakin</label>
                        <label><input type="radio" name="abandon_reason" value="payment_issues"> Masalah pembayaran</label>
                        <label><input type="radio" name="abandon_reason" value="found_better_deal"> Menemukan penawaran yang lebih baik</label>
                        <label><input type="radio" name="abandon_reason" value="changed_mind"> Berubah pikiran</label>
                        <label><input type="radio" name="abandon_reason" value="technical_issues"> Masalah teknis</label>
                        <label><input type="radio" name="abandon_reason" value="other"> Lainnya</label>
                    </div>
                    <textarea placeholder="Alasan lainnya (opsional)" class="other-reason" style="display:none;"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn-submit">Kirim Feedback</button>
                    <button class="btn-continue">Lanjut Belanja</button>
                    <button class="btn-close">Tutup</button>
                </div>
            </div>
        `;
        
        // Add event listeners
        this.setupSurveyEventListeners(modal);
        
        return modal;
    }
    
    setupSurveyEventListeners(modal) {
        const closeBtn = modal.querySelector('.close-btn');
        const btnClose = modal.querySelector('.btn-close');
        const btnSubmit = modal.querySelector('.btn-submit');
        const btnContinue = modal.querySelector('.btn-continue');
        const reasonInputs = modal.querySelectorAll('input[name="abandon_reason"]');
        const otherTextarea = modal.querySelector('.other-reason');
        
        // Show/hide other reason textarea
        reasonInputs.forEach(input => {
            input.addEventListener('change', () => {
                if (input.value === 'other' && input.checked) {
                    otherTextarea.style.display = 'block';
                } else {
                    otherTextarea.style.display = 'none';
                }
            });
        });
        
        // Close modal
        const closeModal = () => {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        };
        
        closeBtn.addEventListener('click', closeModal);
        btnClose.addEventListener('click', closeModal);
        
        // Submit feedback
        btnSubmit.addEventListener('click', () => {
            const selectedReason = modal.querySelector('input[name="abandon_reason"]:checked');
            const otherReason = otherTextarea.value;
            
            if (selectedReason) {
                const reason = {
                    code: selectedReason.value,
                    text: selectedReason.value === 'other' ? otherReason : null
                };
                
                this.trackAbandonment('survey_response', reason);
            } else {
                this.trackAbandonment('survey_no_response');
            }
            
            closeModal();
        });
        
        // Continue shopping
        btnContinue.addEventListener('click', () => {
            this.logCartAction('abandonment_prevented', { method: 'continue_shopping' });
            closeModal();
        });
        
        // Close on overlay click
        modal.querySelector('.modal-overlay').addEventListener('click', closeModal);
    }
    
    updateLastActivity() {
        this.lastActivity = Date.now();
    }
    
    startActivityUpdater() {
        setInterval(() => {
            if (this.isCartPage()) {
                this.sendToServer('/api/update_cart_activity.php', {
                    action: 'activity_update',
                    timestamp: new Date().toISOString()
                });
            }
        }, 60000); // Update every minute
    }
    
    clearTimers() {
        if (this.cartPageTimer) {
            clearTimeout(this.cartPageTimer);
            this.cartPageTimer = null;
        }
        if (this.inactivityTimer) {
            clearTimeout(this.inactivityTimer);
            this.inactivityTimer = null;
        }
    }
    
    sendToServer(endpoint, data) {
        // Use sendBeacon for reliability, fall back to fetch
        const payload = JSON.stringify(data);
        
        if (navigator.sendBeacon) {
            const blob = new Blob([payload], { type: 'application/json' });
            navigator.sendBeacon(endpoint, blob);
        } else {
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: payload,
                keepalive: true
            }).catch(error => {
                console.error('Failed to send tracking data:', error);
            });
        }
    }
}

// Initialize tracking when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new AbandonedCartTracker();
    });
} else {
    new AbandonedCartTracker();
}