// Generate star rating HTML
function generateStarRating($rating, $size = '') {
    $stars = '';
    $sizeClass = $size ? " star-rating-{$size}" : '';
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star text-warning' . $sizeClass . '"></i>';
        } else {
            $stars .= '<i class="far fa-star text-muted' . $sizeClass . '"></i>';
        }
    }
    
    return '<div class="star-rating' . $sizeClass . '">' . $stars . '</div>';
}

// Truncate text
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

// Format date
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

// Format time
function formatTime($date, $format = 'g:i A') {
    return date($format, strtotime($date));
}

// Format date time
function formatDateTime($date, $format = 'M j, Y g:i A') {
    return date($format, strtotime($date));
}