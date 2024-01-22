import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

import './styles/partials.css'

import './styles/components.css'


console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉')





document.addEventListener('DOMContentLoaded', function() {
  var buttons = document.querySelectorAll('.btn-favorite');

  buttons.forEach(function(button) {
      button.addEventListener('click', function(event) {
          event.preventDefault(); 
          var productId = this.dataset.productId;
          var icon = this.querySelector('.bi-star');
          
          fetch('/profil/favorite/toggle/' + productId, {
              method: 'POST',
              headers: {
    
              }
          })
          .then(response => response.json())
          .then(data => {
      
              if (data.status === 'added') {
                  icon.style.fill = 'yellow';
              } else if (data.status === 'removed') {
                  icon.style.fill = 'black';
              }
          })
          .catch(error => {
              console.error('Error:', error);
          });
      });
  });
});