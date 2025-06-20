document.addEventListener('DOMContentLoaded', function() {
    // Loading Screen
    const loadingScreen = document.getElementById('loadingScreen');
    window.addEventListener('load', function() {
        setTimeout(() => {
            loadingScreen.classList.add('hide');
        }, 1000);
    });

    // Scroll Progress Bar
    const scrollProgressBar = document.querySelector('.scroll-progress-bar');
    window.addEventListener('scroll', () => {
        const windowHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (window.scrollY / windowHeight) * 100;
        scrollProgressBar.style.width = scrolled + '%';
    });

    // Enhanced Smooth Scrolling
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                const headerOffset = 80;
                const elementPosition = targetSection.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                // Update active nav link
                navLinks.forEach(navLink => navLink.classList.remove('active'));
                this.classList.add('active');
                
                // Close mobile menu if open
                mobileNav.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
            }
        });
    });

    // Enhanced Header Scroll Effect
    const header = document.querySelector('.header');
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        // Hide/show header on scroll
        if (currentScroll > lastScroll && currentScroll > 500) {
            header.classList.add('header-hidden');
        } else {
            header.classList.remove('header-hidden');
        }
        lastScroll = currentScroll;

        // Show/hide scroll-to-top button
        const scrollToTopBtn = document.querySelector('.scroll-to-top');
        if (currentScroll > 300) {
            scrollToTopBtn.classList.add('show');
        } else {
            scrollToTopBtn.classList.remove('show');
        }
    });

    // Mobile Menu Toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileNav = document.querySelector('.mobile-nav');
    const mobileNavClose = document.querySelector('.mobile-nav-close');
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-links a');

    mobileMenuToggle.addEventListener('click', function() {
        this.classList.toggle('active');
        mobileNav.classList.toggle('active');
        document.body.style.overflow = mobileNav.classList.contains('active') ? 'hidden' : '';
    });

    mobileNavClose.addEventListener('click', function() {
        mobileNav.classList.remove('active');
        mobileMenuToggle.classList.remove('active');
        document.body.style.overflow = '';
    });

    mobileNavLinks.forEach(link => {
        link.addEventListener('click', function() {
            mobileNav.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
            document.body.style.overflow = '';
        });
    });

    // Enhanced Search Box
    const searchBox = document.querySelector('.search-box');
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    
    // Sample search suggestions
    const destinations = [
        'Raja Ampat', 'Jayapura', 'Wamena', 'Baliem Valley', 
        'Lake Sentani', 'Asmat', 'Merauke', 'Biak'
    ];
    
    searchInput.addEventListener('focus', function() {
        searchBox.classList.add('active');
    });
    
    searchInput.addEventListener('blur', function() {
        setTimeout(() => {
            if (this.value === '') {
                searchBox.classList.remove('active');
            }
            searchSuggestions.classList.remove('active');
        }, 200);
    });

    searchInput.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        if (value.length > 0) {
            const filtered = destinations.filter(dest => 
                dest.toLowerCase().includes(value)
            );
            
            if (filtered.length > 0) {
                searchSuggestions.innerHTML = filtered.map(dest => 
                    `<div class="search-suggestion" onclick="selectDestination('${dest}')">${dest}</div>`
                ).join('');
                searchSuggestions.classList.add('active');
            } else {
                searchSuggestions.classList.remove('active');
            }
        } else {
            searchSuggestions.classList.remove('active');
        }
    });

    // Hero Section Animations
    const statNumbers = document.querySelectorAll('.stat-number');
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px'
    };

    const statObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.getAttribute('data-target'));
                animateValue(entry.target, 0, target, 2000);
                statObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    statNumbers.forEach(num => statObserver.observe(num));

    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            element.textContent = Math.floor(progress * (end - start) + start);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Enhanced Intersection Observer for Animations
    const fadeElements = document.querySelectorAll('.fade-in');
    const fadeObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-visible');
                fadeObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    });

    fadeElements.forEach(el => fadeObserver.observe(el));

    // Interest Slider Navigation
    const interestSlider = document.getElementById('interestSlider');
    let isDown = false;
    let startX;
    let scrollLeft;

    if (interestSlider) {
        // Mouse drag functionality
        interestSlider.addEventListener('mousedown', (e) => {
            isDown = true;
            interestSlider.style.cursor = 'grabbing';
            startX = e.pageX - interestSlider.offsetLeft;
            scrollLeft = interestSlider.scrollLeft;
        });

        interestSlider.addEventListener('mouseleave', () => {
            isDown = false;
            interestSlider.style.cursor = 'grab';
        });

        interestSlider.addEventListener('mouseup', () => {
            isDown = false;
            interestSlider.style.cursor = 'grab';
        });

        interestSlider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - interestSlider.offsetLeft;
            const walk = (x - startX) * 2;
            interestSlider.scrollLeft = scrollLeft - walk;
        });

        // Touch support
        let touchStartX = 0;
        interestSlider.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].pageX;
        });

        interestSlider.addEventListener('touchmove', (e) => {
            const touchEndX = e.touches[0].pageX;
            const diff = touchStartX - touchEndX;
            interestSlider.scrollLeft += diff;
            touchStartX = touchEndX;
        });
    }

    // Interest Cards Click Handler
    const interestCards = document.querySelectorAll('.interest-card');
    interestCards.forEach(card => {
        card.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            // Here you would handle the category selection
            console.log('Selected category:', category);
            // You could redirect to a filtered page or open a modal
        });
    });

    // Enhanced Tab Switching for Booking
    const tabButtons = document.querySelectorAll('.tab-btn');
    const bookingForm = document.getElementById('bookingForm');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const tabType = this.getAttribute('data-tab');
            updateBookingForm(tabType);
        });
    });

    function updateBookingForm(tabType) {
        bookingForm.style.opacity = '0';
        
        setTimeout(() => {
            const forms = {
                'stays': `
                    <div class="form-group">
                        <label>Where to?</label>
                        <input type="text" placeholder="Search destinations" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Check-in</label>
                            <input type="date" required>
                        </div>
                        <div class="form-group">
                            <label>Check-out</label>
                            <input type="date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Guests</label>
                        <select required>
                            <option value="">Select guests</option>
                            <option value="1">1 Guest</option>
                            <option value="2">2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4+ Guests</option>
                        </select>
                    </div>
                    <button type="submit" class="submit-btn">Search Stays</button>
                `,
                'flights': `
                    <div class="form-group">
                        <label>From</label>
                        <input type="text" placeholder="Departure city" required>
                    </div>
                    <div class="form-group">
                        <label>To</label>
                        <input type="text" placeholder="Papua destinations" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Departure</label>
                            <input type="date" required>
                        </div>
                        <div class="form-group">
                            <label>Return</label>
                            <input type="date">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Passengers</label>
                        <select required>
                            <option value="">Select passengers</option>
                            <option value="1">1 Passenger</option>
                            <option value="2">2 Passengers</option>
                            <option value="3">3 Passengers</option>
                            <option value="4">4+ Passengers</option>
                        </select>
                    </div>
                    <button type="submit" class="submit-btn">Search Flights</button>
                `,
                'car': `
                    <div class="form-group">
                        <label>Pick-up location</label>
                        <input type="text" placeholder="City or airport" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pick-up date & time</label>
                            <input type="datetime-local" required>
                        </div>
                        <div class="form-group">
                            <label>Return date & time</label>
                            <input type="datetime-local" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Car type</label>
                        <select required>
                            <option value="">Select car type</option>
                            <option value="economy">Economy</option>
                            <option value="compact">Compact</option>
                            <option value="suv">SUV</option>
                            <option value="luxury">Luxury</option>
                        </select>
                    </div>
                    <button type="submit" class="submit-btn">Search Cars</button>
                `,
                'activities': `
                    <div class="form-group">
                        <label>Activity type</label>
                        <select required>
                            <option value="">Select activity</option>
                            <option value="diving">Diving & Snorkeling</option>
                            <option value="trekking">Trekking & Hiking</option>
                            <option value="cultural">Cultural Tours</option>
                            <option value="wildlife">Wildlife Watching</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" placeholder="Where in Papua?" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" required>
                    </div>
                    <div class="form-group">
                        <label>Participants</label>
                        <input type="number" placeholder="Number of people" min="1" required>
                    </div>
                    <button type="submit" class="submit-btn">Search Activities</button>
                `,
                'food': `
                    <div class="form-group">
                        <label>Cuisine type</label>
                        <select required>
                            <option value="">Select cuisine</option>
                            <option value="local">Local Papuan</option>
                            <option value="seafood">Seafood</option>
                            <option value="indonesian">Indonesian</option>
                            <option value="international">International</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" placeholder="City or area" required>
                    </div>
                    <div class="form-group">
                        <label>Date & Time</label>
                        <input type="datetime-local" required>
                    </div>
                    <div class="form-group">
                        <label>Party size</label>
                        <select required>
                            <option value="">Select party size</option>
                            <option value="1">1 Person</option>
                            <option value="2">2 People</option>
                            <option value="3-4">3-4 People</option>
                            <option value="5+">5+ People</option>
                        </select>
                    </div>
                    <button type="submit" class="submit-btn">Find Restaurants</button>
                `,
                'tours': `
                    <div class="form-group">
                        <label>Tour type</label>
                        <select required>
                            <option value="">Select tour type</option>
                            <option value="cultural">Cultural Tours</option>
                            <option value="adventure">Adventure Tours</option>
                            <option value="wildlife">Wildlife Tours</option>
                            <option value="diving">Diving Tours</option>
                            <option value="multi-day">Multi-day Tours</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Duration</label>
                        <select required>
                            <option value="">Select duration</option>
                            <option value="half-day">Half Day</option>
                            <option value="full-day">Full Day</option>
                            <option value="2-3days">2-3 Days</option>
                            <option value="week">1 Week</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" required>
                    </div>
                    <div class="form-group">
                        <label>Group size</label>
                        <input type="number" placeholder="Number of people" min="1" required>
                    </div>
                    <button type="submit" class="submit-btn">Search Tours</button>
                `
            };
            
            bookingForm.innerHTML = forms[tabType] || forms['stays'];
            bookingForm.style.opacity = '1';
            
            // Add date validation
            setupDateValidation();
        }, 300);
    }

    // Initialize with stays form
    updateBookingForm('stays');

    // Form submission handler with validation
    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic form validation
        const inputs = this.querySelectorAll('input[required], select[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value) {
                isValid = false;
                input.classList.add('error');
                showError(input, 'This field is required');
            } else {
                input.classList.remove('error');
                clearError(input);
            }
        });
        
        if (isValid) {
            const submitBtn = this.querySelector('.submit-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            // Simulate processing
            setTimeout(() => {
                // Check if user is logged in
                <?php if(isset($_SESSION['username'])): ?>
                    showNotification('Search completed! Redirecting to results...', 'success');
                    // Here you would redirect to search results
                <?php else: ?>
                    showNotification('Please sign in to complete your booking', 'info');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                <?php endif; ?>
                
                submitBtn.innerHTML = submitBtn.textContent.replace('Processing...', 'Search');
                submitBtn.disabled = false;
            }, 1500);
        }
    });

    // Date validation setup
    function setupDateValidation() {
        const dateInputs = document.querySelectorAll('input[type="date"], input[type="datetime-local"]');
        const today = new Date().toISOString().split('T')[0];
        
        dateInputs.forEach(input => {
            input.setAttribute('min', today);
            
            // Check-in/Check-out validation
            if (input.previousElementSibling && input.previousElementSibling.textContent.includes('Check-out')) {
                const checkinInput = input.closest('.form-row').querySelector('input[type="date"]');
                checkinInput.addEventListener('change', function() {
                    input.setAttribute('min', this.value);
                    if (input.value && input.value < this.value) {
                        input.value = '';
                    }
                });
            }
        });
    }

    // Form validation helpers
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        let errorElement = formGroup.querySelector('.error-message');
        
        if (!errorElement) {
            errorElement = document.createElement('span');
            errorElement.className = 'error-message';
            formGroup.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
    }

    function clearError(input) {
        const formGroup = input.closest('.form-group');
        const errorElement = formGroup.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }
    }

    // Notification System
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Video sound toggle
    window.toggleVideoSound = function(button) {
        const video = button.previousElementSibling;
        const icon = button.querySelector('i');
        
        if (video.muted) {
            video.muted = false;
            icon.className = 'fas fa-volume-up';
        } else {
            video.muted = true;
            icon.className = 'fas fa-volume-mute';
        }
    };

    // Destination modal
    window.showDestinationModal = function() {
        // Here you would show a modal with more destination information
        showNotification('Destination details coming soon!', 'info');
    };

    // Interest slider navigation
    window.slideInterests = function(direction) {
        const scrollAmount = 320; // Width of one card plus gap
        if (direction === 'prev') {
            interestSlider.scrollLeft -= scrollAmount;
        } else {
            interestSlider.scrollLeft += scrollAmount;
        }
    };

    // Chatbot integration
    window.openChatbot = function() {
        // Check if user is logged in
        <?php if(isset($_SESSION['username'])): ?>
            window.location.href = 'users/chatbot/';
        <?php else: ?>
            showNotification('Please sign in to use the AI Travel Assistant', 'info');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        <?php endif; ?>
    };

    // Scroll to top
    window.scrollToTop = function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };

    // Select destination from search
    window.selectDestination = function(destination) {
        searchInput.value = destination;
        searchSuggestions.classList.remove('active');
        // Here you could trigger a search or redirect
    };

    // Parallax effect for hero
    const hero = document.querySelector('.hero');
    const heroContent = document.querySelector('.hero-content');
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        
        if (scrolled < window.innerHeight) {
            hero.style.transform = `translateY(${rate}px)`;
            heroContent.style.transform = `translateY(${rate * 0.5}px)`;
            heroContent.style.opacity = 1 - (scrolled * 0.001);
        }
    });

    // Active section highlighting
    const sections = document.querySelectorAll('section[id]');
    const navItems = document.querySelectorAll('.nav-links a[data-section]');
    
    window.addEventListener('scroll', () => {
        let current = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (pageYOffset >= sectionTop - 200) {
                current = section.getAttribute('id');
            }
        });
        
        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('data-section') === current) {
                item.classList.add('active');
            }
        });
    });

    // Plan cards click handler
    const planCards = document.querySelectorAll('.plan-card');
    planCards.forEach(card => {
        card.addEventListener('click', function() {
            const title = this.querySelector('h3').textContent;
            showNotification(`${title} guide coming soon!`, 'info');
        });
    });

    // Experience icons animation
    const iconItems = document.querySelectorAll('.icon-item');
    iconItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
        item.addEventListener('click', function() {
            const text = this.querySelector('p').textContent;
            showNotification(`Explore ${text} options`, 'info');
        });
    });

    // Lazy loading for images
    const lazyImages = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.getAttribute('data-src');
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));

    // Add CSS for notifications
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 10000;
            max-width: 300px;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification-success {
            border-left: 4px solid #4CAF50;
        }
        
        .notification-success i {
            color: #4CAF50;
        }
        
        .notification-error {
            border-left: 4px solid #f44336;
        }
        
        .notification-error i {
            color: #f44336;
        }
        
        .notification-info {
            border-left: 4px solid #2196F3;
        }
        
        .notification-info i {
            color: #2196F3;
        }
        
        .error-message {
            display: block;
            color: #f44336;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        input.error, select.error {
            border-color: #f44336 !important;
        }
    `;
    document.head.appendChild(style);
});