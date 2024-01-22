// Intercepter la soumission du formulaire
document.getElementById('filtersForm').addEventListener('submit', function(event) {
  event.preventDefault(); // Empêcher la soumission normale du formulaire

  // Collecter les données du formulaire
  const formData = new FormData(this);

  // Envoyer la requête AJAX
  fetch('/home/filtersProducts', {
      method: 'POST',
      body: formData,
  })
  .then(response => response.json())
  .then(data => {
      const productsContainer = document.getElementById('productsContainer'); 
      productsContainer.innerHTML = '';

      data.products.forEach(product => {
          const productCard = document.createElement('div');
          productCard.classList.add('card', 'h-100', 'p-0');
          productCard.innerHTML = `
              <img src="${product.image}" class="card-img-top" alt="${product.name}">
              <div class="card-body">
                  <h5 class="card-title">${product.name}</h5> 
                  <p class="card-text">Prix ${product.price.toFixed(2)} €</p> 
                  <p class="nutri-score">Nutriscore ${product.nutriScore}</p>
                  ${product.promotion ? `<p class="promotion">Promotion ${product.promotion.rising} %</p>` : ''}
              </div>
              <div class="card-footer"> 
                  <a href="/product/details/${product.id}" class="btn btn-success">Détails</a> 
                  <a href="#" class="card-link ms-5"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="green" class="bi bi-cart-plus-fill" viewBox="0 0 16 16">
                  <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0M9 5.5V7h1.5a.5.5 0 0 1 0 1H9v1.5a.5.5 0 0 1-1 0V8H6.5a.5.5 0 0 1 0-1H8V5.5a.5.5 0 0 1 1 0"/>
                  </svg></a>
              </div>
          `;
          productsContainer.appendChild(productCard);
      });
})

  .catch(error => {
      console.error('Erreur AJAX:', error);
  });
});
