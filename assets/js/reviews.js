// Reviews and Ratings JavaScript

// Global variables
let selectedRating = 0;
let selectedFiles = [];
const maxFiles = 5;
const maxImageSize = 5 * 1024 * 1024; // 5MB
const maxVideoSize = 50 * 1024 * 1024; // 50MB
const maxVideoDuration = 10; // seconds

// Initialize review functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeReviewModal();
    initializeStarRating();
    initializeMediaUpload();
    initializeReviewForm();
});

// Initialize review modal
function initializeReviewModal() {
    // Close modal when clicking outside
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeReviewModal();
            }
        });
    }
}

// Open review modal
function openReviewModal(transaksiId, itemType, itemId, itemName, itemImage) {
    const modal = document.getElementById('reviewModal');
    if (!modal) return;
    
    // Reset form
    resetReviewForm();
    
    // Set product info
    document.getElementById('reviewTransaksiId').value = transaksiId;
    document.getElementById('reviewItemType').value = itemType;
    document.getElementById('reviewItemId').value = itemId;
    
    // Update product display
    const productName = document.getElementById('reviewProductName');
    const productImage = document.getElementById('reviewProductImage');
    
    if (productName) productName.textContent = itemName;
    if (productImage) productImage.src = itemImage;
    
    // Show modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close review modal
function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        resetReviewForm();
    }
}

// Reset review form
function resetReviewForm() {
    selectedRating = 0;
    selectedFiles = [];
    
    // Reset stars
    document.querySelectorAll('.star').forEach(star => {
        star.classList.remove('filled', 'active');
    });
    
    // Reset textarea
    const textarea = document.getElementById('reviewText');
    if (textarea) {
        textarea.value = '';
        updateCharCount();
    }
    
    // Clear media preview
    const preview = document.getElementById('mediaPreview');
    if (preview) {
        preview.innerHTML = '';
    }
    
    // Clear any error messages
    const messages = document.querySelectorAll('.review-message');
    messages.forEach(msg => msg.remove());
}

// Initialize star rating
function initializeStarRating() {
    const stars = document.querySelectorAll('.star');
    
    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            selectedRating = index + 1;
            updateStars();
        });
        
        star.addEventListener('mouseenter', function() {
            highlightStars(index + 1);
        });
    });
    
    const starsContainer = document.querySelector('.stars-container');
    if (starsContainer) {
        starsContainer.addEventListener('mouseleave', function() {
            updateStars();
        });
    }
}

// Update star display
function updateStars() {
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < selectedRating) {
            star.classList.add('filled');
            star.classList.remove('active');
        } else {
            star.classList.remove('filled', 'active');
        }
    });
}

// Highlight stars on hover
function highlightStars(rating) {
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

// Initialize media upload
function initializeMediaUpload() {
    const uploadArea = document.querySelector('.upload-area');
    const fileInput = document.getElementById('mediaInput');
    
    if (uploadArea && fileInput) {
        // Click to upload
        uploadArea.addEventListener('click', () => fileInput.click());
        
        // Drag and drop
        uploadArea.addEventListener('dragover', handleDragOver);
        uploadArea.addEventListener('dragleave', handleDragLeave);
        uploadArea.addEventListener('drop', handleDrop);
        
        // File input change
        fileInput.addEventListener('change', handleFileSelect);
    }
}

// Handle drag over
function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
}

// Handle drag leave
function handleDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

// Handle drop
function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    
    const files = Array.from(e.dataTransfer.files);
    processFiles(files);
}

// Handle file select
function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    processFiles(files);
}

// Process selected files
function processFiles(files) {
    const remainingSlots = maxFiles - selectedFiles.length;
    
    if (remainingSlots <= 0) {
        showMessage('Maksimal 5 file media yang dapat diunggah', 'error');
        return;
    }
    
    const filesToAdd = files.slice(0, remainingSlots);
    
    filesToAdd.forEach(file => {
        if (validateFile(file)) {
            selectedFiles.push(file);
            addMediaPreview(file);
        }
    });
}

// Validate file
function validateFile(file) {
    const allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    const allowedVideoTypes = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo'];
    
    if (allowedImageTypes.includes(file.type)) {
        if (file.size > maxImageSize) {
            showMessage('Ukuran gambar maksimal 5MB', 'error');
            return false;
        }
        return true;
    } else if (allowedVideoTypes.includes(file.type)) {
        if (file.size > maxVideoSize) {
            showMessage('Ukuran video maksimal 50MB', 'error');
            return false;
        }
        // Note: Video duration validation should be done server-side
        return true;
    } else {
        showMessage('Format file tidak didukung', 'error');
        return false;
    }
}

// Add media preview
function addMediaPreview(file) {
    const preview = document.getElementById('mediaPreview');
    if (!preview) return;
    
    const mediaItem = document.createElement('div');
    mediaItem.className = 'media-item';
    
    const removeBtn = document.createElement('button');
    removeBtn.className = 'remove-media';
    removeBtn.innerHTML = '×';
    removeBtn.onclick = () => removeMedia(file);
    
    if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        mediaItem.appendChild(img);
    } else if (file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);
        video.controls = true;
        mediaItem.appendChild(video);
    }
    
    mediaItem.appendChild(removeBtn);
    preview.appendChild(mediaItem);
}

// Remove media
function removeMedia(fileToRemove) {
    selectedFiles = selectedFiles.filter(file => file !== fileToRemove);
    
    // Rebuild preview
    const preview = document.getElementById('mediaPreview');
    if (preview) {
        preview.innerHTML = '';
        selectedFiles.forEach(file => addMediaPreview(file));
    }
}

// Initialize review form submission
function initializeReviewForm() {
    const submitBtn = document.getElementById('submitReviewBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', submitReview);
    }
    
    // Character count
    const textarea = document.getElementById('reviewText');
    if (textarea) {
        textarea.addEventListener('input', updateCharCount);
    }
}

// Update character count
function updateCharCount() {
    const textarea = document.getElementById('reviewText');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        const length = textarea.value.length;
        charCount.textContent = `${length}/1000`;
        
        if (length > 1000) {
            charCount.style.color = '#e74c3c';
        } else {
            charCount.style.color = '#7f8c8d';
        }
    }
}

// Submit review
async function submitReview() {
    // Validate form
    if (!validateReviewForm()) return;
    
    const submitBtn = document.getElementById('submitReviewBtn');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading-spinner"></span> Mengirim...';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('transaksi_id', document.getElementById('reviewTransaksiId').value);
    formData.append('item_type', document.getElementById('reviewItemType').value);
    formData.append('item_id', document.getElementById('reviewItemId').value);
    formData.append('rating', selectedRating);
    formData.append('review_text', document.getElementById('reviewText').value);
    
    // Add media files
    selectedFiles.forEach((file, index) => {
        formData.append('media[]', file);
    });
    
    try {
        const response = await fetch('../reviews/submit_review.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Review berhasil disimpan!', 'success');
            
            // Close modal after delay
            setTimeout(() => {
                closeReviewModal();
                // Reload page to update review status
                location.reload();
            }, 1500);
        } else {
            showMessage(result.message || 'Terjadi kesalahan', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        showMessage('Terjadi kesalahan jaringan', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Validate review form
function validateReviewForm() {
    if (selectedRating === 0) {
        showMessage('Silakan pilih rating', 'error');
        return false;
    }
    
    const reviewText = document.getElementById('reviewText').value.trim();
    if (reviewText.length < 10) {
        showMessage('Review minimal 10 karakter', 'error');
        return false;
    }
    
    if (reviewText.length > 1000) {
        showMessage('Review maksimal 1000 karakter', 'error');
        return false;
    }
    
    return true;
}

// Show message
function showMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.review-message');
    existingMessages.forEach(msg => msg.remove());
    
    const messageEl = document.createElement('div');
    messageEl.className = `review-message ${type}`;
    messageEl.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    const modalContent = document.querySelector('.review-modal-content');
    if (modalContent) {
        modalContent.insertBefore(messageEl, modalContent.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            messageEl.remove();
        }, 5000);
    }
}

// Load reviews for a product
async function loadReviews(itemType, itemId, page = 1) {
    try {
        const response = await fetch(`../reviews/get_reviews.php?item_type=${itemType}&item_id=${itemId}&page=${page}`);
        const data = await response.json();
        
        if (data.success) {
            displayReviews(data);
        }
    } catch (error) {
        console.error('Error loading reviews:', error);
    }
}

// Display reviews
function displayReviews(data) {
    // Update summary
    updateReviewSummary(data.summary);
    
    // Display reviews list
    const reviewsList = document.getElementById('reviewsList');
    if (reviewsList) {
        if (data.reviews.length === 0 && data.pagination.current_page === 1) {
            reviewsList.innerHTML = '<p class="no-reviews">Belum ada review untuk produk ini</p>';
        } else {
            data.reviews.forEach(review => {
                reviewsList.appendChild(createReviewElement(review));
            });
        }
        
        // Update load more button
        updateLoadMoreButton(data.pagination);
    }
}

// Update review summary
function updateReviewSummary(summary) {
    const avgRating = document.getElementById('averageRating');
    const totalReviews = document.getElementById('totalReviews');
    
    if (avgRating) avgRating.textContent = summary.average_rating.toFixed(1);
    if (totalReviews) totalReviews.textContent = `${summary.total_reviews} reviews`;
    
    // Update rating bars
    for (let i = 5; i >= 1; i--) {
        const bar = document.getElementById(`rating${i}Bar`);
        const count = document.getElementById(`rating${i}Count`);
        
        if (bar) bar.style.width = `${summary.rating_percentages[i]}%`;
        if (count) count.textContent = summary.rating_distribution[i];
    }
}

// Create review element
function createReviewElement(review) {
    const reviewEl = document.createElement('div');
    reviewEl.className = 'review-item';
    
    const starsHtml = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
    
    let mediaHtml = '';
    if (review.media.length > 0) {
        mediaHtml = '<div class="review-media">';
        review.media.forEach(media => {
            if (media.type === 'image') {
                mediaHtml += `<div class="review-media-item" onclick="viewMedia('${media.url}')">
                    <img src="${media.url}" alt="Review image">
                </div>`;
            } else if (media.type === 'video') {
                mediaHtml += `<div class="review-media-item" onclick="viewMedia('${media.url}', 'video')">
                    <video src="${media.url}"></video>
                </div>`;
            }
        });
        mediaHtml += '</div>';
    }
    
    reviewEl.innerHTML = `
        <div class="review-header">
            <div class="reviewer-info">
                <div class="reviewer-avatar">
                    <img src="${review.user.avatar}" alt="${review.user.name}">
                </div>
                <div class="reviewer-details">
                    <h4>${review.user.name}</h4>
                    <div class="review-date">${review.formatted_date}</div>
                </div>
            </div>
            <div class="review-rating">${starsHtml}</div>
        </div>
        <div class="review-content">${review.text}</div>
        ${mediaHtml}
        <div class="review-actions">
            <div class="helpful-buttons">
                Apakah review ini membantu?
                <button class="helpful-btn ${review.user_vote === '1' ? 'voted' : ''}" 
                        onclick="voteHelpful(${review.id}, true)">
                    <i class="fas fa-thumbs-up"></i> 
                    <span>${review.helpful_count}</span>
                </button>
                <button class="helpful-btn ${review.user_vote === '0' ? 'voted' : ''}" 
                        onclick="voteHelpful(${review.id}, false)">
                    <i class="fas fa-thumbs-down"></i> 
                    <span>${review.not_helpful_count}</span>
                </button>
            </div>
            ${review.is_verified ? '<div class="verified-badge"><i class="fas fa-check-circle"></i> Verified Purchase</div>' : ''}
        </div>
    `;
    
    return reviewEl;
}

// Vote helpful
async function voteHelpful(reviewId, isHelpful) {
    try {
        const formData = new FormData();
        formData.append('review_id', reviewId);
        formData.append('is_helpful', isHelpful);
        
        const response = await fetch('../reviews/vote_helpful.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success && result.message) {
            alert(result.message);
        } else {
            // Reload reviews to update vote counts
            location.reload();
        }
    } catch (error) {
        console.error('Error voting:', error);
    }
}

// View media in modal or lightbox
function viewMedia(url, type = 'image') {
    // Implementation for media viewer
    // You can use a lightbox library or create a simple modal
    window.open(url, '_blank');
}

// Update load more button
function updateLoadMoreButton(pagination) {
    const loadMoreBtn = document.getElementById('loadMoreReviews');
    if (loadMoreBtn) {
        if (pagination.has_next) {
            loadMoreBtn.style.display = 'block';
            loadMoreBtn.onclick = () => loadReviews(
                document.getElementById('reviewItemType').value,
                document.getElementById('reviewItemId').value,
                pagination.current_page + 1
            );
        } else {
            loadMoreBtn.style.display = 'none';
        }
    }
}