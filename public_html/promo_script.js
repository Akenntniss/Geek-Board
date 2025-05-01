document.addEventListener('DOMContentLoaded', function() {
    // Navigation burger menu
    const burger = document.querySelector('.burger');
    const nav = document.querySelector('.nav-links');
    const navLinks = document.querySelectorAll('.nav-links li');
    
    if (burger) {
        burger.addEventListener('click', () => {
            // Toggle Nav
            nav.classList.toggle('nav-active');
            
            // Animate Links
            navLinks.forEach((link, index) => {
                if (link.style.animation) {
                    link.style.animation = '';
                } else {
                    link.style.animation = `navLinkFade 0.5s ease forwards ${index / 7 + 0.3}s`;
                }
            });
            
            // Burger Animation
            burger.classList.toggle('toggle');
        });
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            // Close mobile menu if open
            if (nav.classList.contains('nav-active')) {
                nav.classList.remove('nav-active');
                burger.classList.remove('toggle');
                navLinks.forEach(link => {
                    link.style.animation = '';
                });
            }
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Tabs functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    if (tabButtons.length > 0) {
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                
                // Remove active class from all buttons and tabs
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                document.querySelectorAll('.tab-pane').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Add active class to clicked button and corresponding tab
                button.classList.add('active');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
    }

    // Testimonial slider
    const testimonials = document.querySelectorAll('.testimonial');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    let currentSlide = 0;
    
    if (testimonials.length > 0) {
        // Initialize testimonials display (hide all except first)
        testimonials.forEach((testimonial, index) => {
            if (index !== 0) {
                testimonial.style.display = 'none';
            }
        });
        
        // Show a specific slide
        function showSlide(index) {
            if (index < 0) {
                currentSlide = testimonials.length - 1;
            } else if (index >= testimonials.length) {
                currentSlide = 0;
            } else {
                currentSlide = index;
            }
            
            // Hide all testimonials
            testimonials.forEach(testimonial => {
                testimonial.style.display = 'none';
            });
            
            // Remove active class from all dots
            dots.forEach(dot => {
                dot.classList.remove('active');
            });
            
            // Show current testimonial and activate dot
            testimonials[currentSlide].style.display = 'block';
            dots[currentSlide].classList.add('active');
        }
        
        // Previous button
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                showSlide(currentSlide - 1);
            });
        }
        
        // Next button
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                showSlide(currentSlide + 1);
            });
        }
        
        // Dot navigation
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
            });
        });
        
        // Auto rotate testimonials
        setInterval(() => {
            showSlide(currentSlide + 1);
        }, 6000);
    }

    // Video placeholder click handler
    const videoPlaceholder = document.querySelector('.video-placeholder');
    
    if (videoPlaceholder) {
        videoPlaceholder.addEventListener('click', function() {
            // This would normally embed a real video
            // For demo purposes, we'll just alert
            alert('La vidéo de dégustation des saveurs El Tornado Del Fuego serait lancée ici dans un cas réel.');
        });
    }

    // Form submission with validation
    const orderForm = document.getElementById('trial-form');
    const flavorSelect = document.getElementById('flavors');
    
    if (orderForm) {
        // Helper for multi-select display
        if (flavorSelect) {
            // Create a custom style for the multi-select
            const selectedFlavors = document.createElement('div');
            selectedFlavors.className = 'selected-flavors';
            selectedFlavors.style.marginTop = '10px';
            selectedFlavors.style.display = 'flex';
            selectedFlavors.style.flexWrap = 'wrap';
            selectedFlavors.style.gap = '5px';
            flavorSelect.parentNode.appendChild(selectedFlavors);
            
            // Update visual chips when selections change
            flavorSelect.addEventListener('change', function() {
                selectedFlavors.innerHTML = '';
                
                Array.from(this.selectedOptions).forEach(option => {
                    const chip = document.createElement('div');
                    chip.className = 'flavor-chip';
                    chip.style.backgroundColor = 'rgba(232, 52, 0, 0.1)';
                    chip.style.color = '#e83400';
                    chip.style.padding = '5px 10px';
                    chip.style.borderRadius = '15px';
                    chip.style.fontSize = '0.9rem';
                    chip.style.display = 'flex';
                    chip.style.alignItems = 'center';
                    
                    const text = document.createTextNode(option.text);
                    chip.appendChild(text);
                    
                    const removeBtn = document.createElement('span');
                    removeBtn.innerHTML = '&times;';
                    removeBtn.style.marginLeft = '5px';
                    removeBtn.style.cursor = 'pointer';
                    removeBtn.style.fontWeight = 'bold';
                    
                    removeBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        option.selected = false;
                        chip.remove();
                        
                        // Trigger change event manually
                        const event = new Event('change');
                        flavorSelect.dispatchEvent(event);
                    });
                    
                    chip.appendChild(removeBtn);
                    selectedFlavors.appendChild(chip);
                });
            });
        }
        
        orderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const flavors = Array.from(flavorSelect.selectedOptions).map(opt => opt.value);
            
            if (!name || !email) {
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }
            
            if (flavors.length === 0) {
                alert('Veuillez sélectionner au moins une saveur.');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Veuillez entrer une adresse e-mail valide.');
                return;
            }
            
            // Form would be submitted to server here
            alert('Merci pour votre commande ! Nous vous contacterons très bientôt pour confirmer les détails de livraison des saveurs sélectionnées : ' + 
                  Array.from(flavorSelect.selectedOptions).map(opt => opt.text).join(', '));
            orderForm.reset();
            
            // Clear selected flavors display
            const selectedFlavors = document.querySelector('.selected-flavors');
            if (selectedFlavors) {
                selectedFlavors.innerHTML = '';
            }
        });
    }

    // Sticky header on scroll
    const header = document.querySelector('nav');
    let scrollPosition = window.scrollY;
    
    window.addEventListener('scroll', function() {
        scrollPosition = window.scrollY;
        
        if (scrollPosition > 10) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Animation on scroll for features (saveur cards)
    const featureCards = document.querySelectorAll('.feature-card');
    
    if (featureCards.length > 0) {
        // Add initial hidden class
        featureCards.forEach(card => {
            card.classList.add('hidden');
        });
        
        // Check if element is in viewport
        function isInViewport(element) {
            const rect = element.getBoundingClientRect();
            return (
                rect.top <= (window.innerHeight || document.documentElement.clientHeight) * 0.8
            );
        }
        
        // Show elements when scrolled into view
        function checkVisibility() {
            featureCards.forEach((card, index) => {
                if (isInViewport(card)) {
                    // Add staggered delay to create a wave effect
                    setTimeout(() => {
                        card.classList.remove('hidden');
                        card.classList.add('fadeIn');
                    }, index * 100);
                }
            });
        }
        
        // Check visibility on load
        checkVisibility();
        
        // Check visibility on scroll
        window.addEventListener('scroll', checkVisibility);
    }
    
    // Color theming for flavor cards
    const flavorCards = document.querySelectorAll('.feature-card');
    
    // Define colors for different types of flavors
    const flavorColors = {
        'Citron Frappé': '#f9e076', // Jaune citron
        'Pomme Caramélisée': '#c5a050', // Caramel
        'Fuego Picante': '#ff5252', // Rouge épicé
        'Fraise Bonbon': '#ff7eb9', // Rose bonbon
        'Café Crémeux': '#8b5a2b', // Brun café
        'Vanille Bourbon': '#f3e5ab', // Crème vanille
        'Menthe Fraîche': '#4caf50', // Vert menthe
        'Brise Alpine': '#4fa0e8'  // Bleu frais
    };
    
    // Apply color accents to each card
    if (flavorCards.length > 0) {
        flavorCards.forEach(card => {
            const flavorName = card.querySelector('h3').textContent;
            const accentColor = flavorColors[flavorName] || '#e83400'; // Default to brand orange
            
            // Apply subtle color to the card
            card.querySelector('.feature-icon').style.backgroundColor = `${accentColor}20`; // 20% opacity
            card.querySelector('.feature-icon i').style.color = accentColor;
            
            // Change top border gradient
            card.style.setProperty('--flavor-color', accentColor);
            card.style.borderTop = `none`;
            card.style.boxShadow = `0 4px 6px ${accentColor}20`;
            
            // Apply hover effect
            card.addEventListener('mouseover', () => {
                card.style.boxShadow = `0 10px 20px ${accentColor}30`;
                card.style.transform = 'translateY(-10px)';
            });
            
            card.addEventListener('mouseout', () => {
                card.style.boxShadow = `0 4px 6px ${accentColor}20`;
                card.style.transform = 'translateY(0)';
            });
            
            // Add a color indicator
            card.style.position = 'relative';
            card.style.overflow = 'hidden';
            
            const colorIndicator = document.createElement('div');
            colorIndicator.style.position = 'absolute';
            colorIndicator.style.top = '0';
            colorIndicator.style.left = '0';
            colorIndicator.style.width = '100%';
            colorIndicator.style.height = '5px';
            colorIndicator.style.background = `linear-gradient(90deg, ${accentColor}, ${accentColor}aa)`;
            
            card.insertBefore(colorIndicator, card.firstChild);
        });
    }
}); 