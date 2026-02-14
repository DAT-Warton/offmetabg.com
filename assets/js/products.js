/**
 * Product Cards - Read More/Less Toggle
 */
function toggleDescription(button) {
    const wrapper = button.closest('.product-description-wrapper');
    const readMoreText = button.querySelector('.read-more-text');
    const readLessText = button.querySelector('.read-less-text');
    
    if (wrapper.classList.contains('expanded')) {
        // Collapse
        wrapper.classList.remove('expanded');
        readMoreText.style.display = '';
        readLessText.style.display = 'none';
        
        // Smooth scroll to card top if needed
        const card = button.closest('.product-card');
        if (card) {
            const cardTop = card.getBoundingClientRect().top + window.pageYOffset - 100;
            if (window.pageYOffset > cardTop) {
                window.scrollTo({ top: cardTop, behavior: 'smooth' });
            }
        }
    } else {
        // Expand
        wrapper.classList.add('expanded');
        readMoreText.style.display = 'none';
        readLessText.style.display = '';
    }
}
