document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for navigation links
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Header scroll effect
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
    });

    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all fade-in elements
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach(el => observer.observe(el));

    // Interest slider functionality
    const interestSlider = document.getElementById('interestSlider');
    let isDown = false;
    let startX;
    let scrollLeft;

    if (interestSlider) {
        interestSlider.addEventListener('mousedown', (e) => {
            isDown = true;
            interestSlider.classList.add('active');
            startX = e.pageX - interestSlider.offsetLeft;
            scrollLeft = interestSlider.scrollLeft;
        });

        interestSlider.addEventListener('mouseleave', () => {
            isDown = false;
            interestSlider.classList.remove('active');
        });

        interestSlider.addEventListener('mouseup', () => {
            isDown = false;
            interestSlider.classList.remove('active');
        });

        interestSlider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - interestSlider.offsetLeft;
            const walk = (x - startX) * 2;
            interestSlider.scrollLeft = scrollLeft - walk;
        });

        // Touch support for mobile
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

    // Tab switching for booking section
    const tabButtons = document.querySelectorAll('.tab-btn');
    const bookingForm = document.getElementById('bookingForm');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Animate button click
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
            
            // Update form based on selected tab
            const tabType = this.getAttribute('data-tab');
            updateBookingForm(tabType);
        });
    });

    function updateBookingForm(tabType) {
        // Fade out current form
        bookingForm.style.opacity = '0';
        
        setTimeout(() => {
            switch(tabType) {
                case 'stays':
                    bookingForm.innerHTML = `
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
                            <input type="number" placeholder="2 adults" min="1" required>
                        </div>
                        <button type="submit" class="submit-btn">Search Stays</button>
                    `;
                    break;
                case 'flights':
                    bookingForm.innerHTML = `
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
                        <button type="submit" class="submit-btn">Search Flights</button>
                    `;
                    break;
                case 'car':
                    bookingForm.innerHTML = `
                        <div class="form-group">
                            <label>Pick-up location</label>
                            <input type="text" placeholder="City or airport" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Pick-up date</label>
                                <input type="date" required>
                            </div>
                            <div class="form-group">
                                <label>Return date</label>
                                <input type="date" required>
                            </div>
                        </div>
                        <button type="submit" class="submit-btn">Search Cars</button>
                    `;
                    break;
                case 'activities':
                    bookingForm.innerHTML = `
                        <div class="form-group">
                            <label>What are you looking for?</label>
                            <input type="text" placeholder="Tours, activities, experiences" required>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" placeholder="Where in Papua?" required>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" required>
                        </div>
                        <button type="submit" class="submit-btn">Search Activities</button>
                    `;
                    break;
                case 'food':
                    bookingForm.innerHTML = `
                        <div class="form-group">
                            <label>Restaurant or cuisine type</label>
                            <input type="text" placeholder="Local food, seafood, etc." required>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" placeholder="City or area" required>
                        </div>
                        <div class="form-group">
                            <label>Date & Time</label>
                            <input type="datetime-local" required>
                        </div>
                        <button type="submit" class="submit-btn">Find Restaurants</button>
                    `;
                    break;
                case 'tours':
                    bookingForm.innerHTML = `
                        <div class="form-group">
                            <label>Tour type</label>
                            <select required>
                                <option value="">Select tour type</option>
                                <option value="cultural">Cultural Tours</option>
                                <option value="adventure">Adventure Tours</option>
                                <option value="wildlife">Wildlife Tours</option>
                                <option value="diving">Diving Tours</option>
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
                    `;
                    break;
            }
            
            // Fade in new form
            bookingForm.style.opacity = '1';
        }, 300);
    }

    // Initialize with stays form
    updateBookingForm('stays');

    // Form submission handler
    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        // Add ripple effect to button
        const submitBtn = this.querySelector('.submit-btn');
        submitBtn.style.transform = 'scale(0.98)';
        setTimeout(() => {
            submitBtn.style.transform = 'scale(1)';
            // Here you would handle the actual form submission
            alert('Please sign in to complete your booking');
            window.location.href = 'login.php';
        }, 200);
    });

    // Search box animation
    const searchBox = document.querySelector('.search-box');
    const searchInput = searchBox.querySelector('input');
    
    searchInput.addEventListener('focus', function() {
        searchBox.classList.add('active');
    });
    
    searchInput.addEventListener('blur', function() {
        if (this.value === '') {
            searchBox.classList.remove('active');
        }
    });

    // Add parallax effect to hero section only
    const hero = document.querySelector('.hero');
    const heroHeight = hero.offsetHeight;
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        // Only apply parallax when hero is in view
        if (scrolled < heroHeight) {
            const parallax = scrolled * 0.5;
            hero.style.transform = `translateY(${parallax}px)`;
        }
    });

    // Animate numbers/stats if they exist
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

    // Experience icons hover effect
    const iconItems = document.querySelectorAll('.icon-item');
    iconItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.05)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Plan cards stagger animation
    const planCards = document.querySelectorAll('.plan-card');
    planCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        observer.observe(card);
    });

    // Interest cards hover effect
    const interestCards = document.querySelectorAll('.interest-card');
    interestCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
            this.style.boxShadow = '0 15px 30px rgba(0,0,0,0.2)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
        });
    });

    // Loading animation for images
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('load', function() {
            this.classList.add('loaded');
        });
    });

    // Video play on hover
    const videos = document.querySelectorAll('video');
    videos.forEach(video => {
        video.addEventListener('mouseenter', () => video.play());
        video.addEventListener('mouseleave', () => video.pause());
    });
});