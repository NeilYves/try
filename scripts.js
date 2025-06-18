document.addEventListener('DOMContentLoaded', function() {
    // Animation for statistics numbers
    animateCounters();
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Create floating particles animation
    createFloatingParticles();
    
    // Add intersection observer for animations
    initializeScrollAnimations();
    
    // Enhanced card interactions
    initializeCardAnimations();
    
    // Add loading animations
    addLoadingAnimations();
    
    // Mobile sidebar toggle
    const sidebarToggleBtn = document.createElement('button');
    sidebarToggleBtn.classList.add('btn', 'btn-primary', 'sidebar-toggle');
    sidebarToggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
    sidebarToggleBtn.style.position = 'fixed';
    sidebarToggleBtn.style.top = '10px';
    sidebarToggleBtn.style.left = '10px';
    sidebarToggleBtn.style.zIndex = '1040';
    sidebarToggleBtn.style.display = 'none';
    sidebarToggleBtn.style.borderRadius = '50%';
    sidebarToggleBtn.style.width = '50px';
    sidebarToggleBtn.style.height = '50px';
    sidebarToggleBtn.style.boxShadow = '0 4px 15px rgba(0,0,0,0.2)';
    
    document.body.appendChild(sidebarToggleBtn);
    
    sidebarToggleBtn.addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
        this.style.transform = this.style.transform === 'rotate(90deg)' ? 'rotate(0deg)' : 'rotate(90deg)';
    });
    
    // Show/hide toggle button based on screen size
    function checkScreenSize() {
        if (window.innerWidth < 768) {
            sidebarToggleBtn.style.display = 'block';
        } else {
            sidebarToggleBtn.style.display = 'none';
            document.getElementById('sidebar').classList.remove('show');
        }
    }
    
    window.addEventListener('resize', checkScreenSize);
    checkScreenSize(); // Initial check
    
    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Show loading overlay on form submission
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', showLoadingOverlay);
    });

    // Show loading overlay on link clicks (excluding those with data-bs-toggle for modals/dropdowns)
    document.querySelectorAll('a:not([data-bs-toggle])').forEach(link => {
        link.addEventListener('click', function(event) {
            // Only show loading for links that navigate to a new page
            if (this.href && this.href !== '#' && this.target !== '_blank') {
                showLoadingOverlay();
            }
        });
    });

    // Hide loading overlay on page load completion (in case of back/forward navigation or initial load)
    window.addEventListener('load', hideLoadingOverlay);
});

// Enhanced function to animate counter numbers with easing
function animateCounters() {
    const counters = document.querySelectorAll('.count-number');
    
    counters.forEach((counter, index) => {
            const target = parseInt(counter.getAttribute('data-target') || '0');
        const duration = 2000; // 2 seconds
        const startTime = performance.now() + (index * 200); // Stagger animations
        
        // Add initial loading class
        counter.classList.add('loading');
        
        const animate = (currentTime) => {
            if (currentTime < startTime) {
                requestAnimationFrame(animate);
                return;
            }
            
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function (ease-out cubic)
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            
            const current = Math.floor(target * easeProgress);
            counter.innerText = current;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                counter.innerText = target;
                counter.classList.remove('loading');
            }
        };
        
        requestAnimationFrame(animate);
    });
}

// Create floating particles animation
function createFloatingParticles() {
    const particleContainer = document.createElement('div');
    particleContainer.className = 'floating-particles';
    document.body.appendChild(particleContainer);
    
    function createParticle() {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        // Random position
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 6 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
        
        // Random size
        const size = Math.random() * 4 + 2;
        particle.style.width = size + 'px';
        particle.style.height = size + 'px';
        
        // Random color
        const colors = ['rgba(102, 126, 234, 0.3)', 'rgba(118, 75, 162, 0.3)', 'rgba(52, 168, 83, 0.3)', 'rgba(251, 188, 5, 0.3)'];
        particle.style.background = colors[Math.floor(Math.random() * colors.length)];
        
        particleContainer.appendChild(particle);
        
        // Remove particle after animation
        setTimeout(() => {
            if (particle.parentNode) {
                particle.parentNode.removeChild(particle);
            }
        }, 6000);
    }
    
    // Create particles periodically
    setInterval(createParticle, 300);
}

// Initialize scroll animations with Intersection Observer
function initializeScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                
                // Add special effects for cards
                if (entry.target.classList.contains('stat-card')) {
                    setTimeout(() => {
                        entry.target.style.transform = 'translateY(0) scale(1)';
                    }, 100);
                }
            }
        });
    }, observerOptions);
    
    // Observe all cards and major elements
    document.querySelectorAll('.card, .stat-card, .announcement-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        observer.observe(el);
    });
}

// Enhanced card animations
function initializeCardAnimations() {
    const cards = document.querySelectorAll('.stat-card');
    
    cards.forEach(card => {
        // Add ripple effect on click
        card.addEventListener('click', function(e) {
            const ripple = document.createElement('div');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
                z-index: 10;
            `;
            
            this.style.position = 'relative';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
        
        // Enhanced hover effects
        card.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.stat-icon-bg');
            if (icon) {
                icon.style.transform = 'scale(1.2) rotate(15deg)';
            }
            
            const number = this.querySelector('.count-number');
            if (number) {
                number.style.transform = 'scale(1.05)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.stat-icon-bg');
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
            
            const number = this.querySelector('.count-number');
            if (number) {
                number.style.transform = 'scale(1)';
            }
        });
    });
}

// Add loading animations
function addLoadingAnimations() {
    // Shimmer effect for loading elements
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }
        
        @keyframes ripple {
            to { transform: scale(2); opacity: 0; }
        }
        
        .shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200px 100%;
            animation: shimmer 1.5s infinite;
        }
    `;
    document.head.appendChild(style);
}

// Add parallax effect for background elements
function initializeParallax() {
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.barangay-info-card');
        
        parallaxElements.forEach(element => {
            const speed = 0.5;
            element.style.transform = `translateY(${scrolled * speed}px)`;
        });
    });
}

// Initialize parallax effect
initializeParallax();

// Add pulse animation to important elements
function addPulseAnimations() {
    const importantElements = document.querySelectorAll('.btn-primary, .stat-link');
    
    importantElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.animation = 'pulse 1s infinite';
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.animation = '';
        });
    });
}

// Initialize pulse animations
addPulseAnimations();

// Add smooth transitions for page elements
document.addEventListener('DOMContentLoaded', function() {
    // Add transition delays for staggered animations
    const cards = document.querySelectorAll('.stat-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Add breathing animation to the logo
    const logo = document.querySelector('.brgy-logo');
    if (logo) {
        setInterval(() => {
            logo.style.transform = 'scale(1.05)';
            setTimeout(() => {
                logo.style.transform = 'scale(1)';
            }, 1000);
        }, 3000);
    }
});

// Add typewriter effect for titles
function typewriterEffect(element, text, speed = 100) {
    element.innerHTML = '';
    let i = 0;
    
    function type() {
        if (i < text.length) {
            element.innerHTML += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    
    type();
}

// Global Loading Overlay functions
function showLoadingOverlay() {
    document.getElementById('globalLoadingOverlay').classList.add('show');
}

function hideLoadingOverlay() {
    document.getElementById('globalLoadingOverlay').classList.remove('show');
}
