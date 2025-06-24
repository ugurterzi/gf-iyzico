window.onload = function () {
  const token = new URLSearchParams(window.location.search).get('token');
  if (!token) return;
  
  const tokenKey = `iyzico_token_${token}`;
  const redirectUrl = typeof iyzicoRedirectUrl === 'undefined' ? '/' : iyzicoRedirectUrl;
  const delay = typeof iyzicoRedirectDelay === 'undefined' ? 5 : parseInt(iyzicoRedirectDelay, 10);
  const countdownEl = document.getElementById('iyzico-countdown');
  
  // Check if this page is showing a redirect template (has countdown element)
  const isRedirectPage = countdownEl !== null;
  
  if (isRedirectPage) {
    // This is a redirect page - start countdown and redirect
    let secondsLeft = delay;
    
    if (!isNaN(secondsLeft) && secondsLeft > 0) {
      countdownEl.textContent = secondsLeft;
      
      const interval = setInterval(() => {
        secondsLeft--;
        countdownEl.textContent = secondsLeft;
        
        if (secondsLeft <= 0) {
          clearInterval(interval);
          window.location.href = redirectUrl;
        }
      }, 1000);
    } else {
      // No delay, redirect immediately
      window.location.href = redirectUrl;
    }
  } else {
    // This is the success page - only mark as used and add back button protection
    
    // Check if sessionStorage is supported
    if (typeof(Storage) !== "undefined") {
      sessionStorage.setItem(tokenKey, 'used');
    }
    
    // Add back button protection
    window.addEventListener('pageshow', function(event) {
      // Only act on back/forward navigation or cached page loads
      if (event.persisted) {
        // Page was loaded from cache (back button)
        const successContent = document.querySelector('.iyzico-success-content, .payment-confirmation');
        if (successContent) {
          successContent.innerHTML = '<div class="iyzico-expired-message">' +
            '<h3>Bu sayfa zaten görüntülendi</h3>' +
            '<p>Ana sayfaya yönlendiriliyorsunuz...</p>' +
            '</div>';
          
          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 2000);
        }
      }
    });
  }
};