document.addEventListener('DOMContentLoaded', () => {
  const skuMap = window.llmsgaaSkuMap || {};

  const detailModal = document.getElementById('llmsgaa-pass-modal');
  const redeemModal = document.getElementById('llmsgaa-redeem-modal');

  function openModal(modal) {
    modal.style.display = 'block';
  }

  function closeModal(modal) {
    modal.style.display = 'none';
  }

  // Close buttons
  document.querySelectorAll('.llmsgaa-modal-close').forEach(el => {
    el.addEventListener('click', () => {
      closeModal(detailModal);
      closeModal(redeemModal);
    });
  });

  // View pass details
  document.querySelectorAll('.llmsgaa-pass-details').forEach(el => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      const title = el.dataset.title;
      const date = el.dataset.date;
      const email = el.dataset.email;

      const itemsRaw = el.dataset.items || '[]';
      let items = [];

      try {
        items = JSON.parse(itemsRaw);
        if (!Array.isArray(items)) {
          items = [];
        }
      } catch (err) {
        console.warn('Invalid items JSON:', itemsRaw, err);
        items = [];
      }

      let html = `<h3>${title}</h3>`;
      html += `<p><strong>Date Purchased:</strong> ${date}</p>`;
      html += `<p><strong>Buyer Email:</strong> ${email}</p>`;
      if (items.length) {
        html += '<h4>Items:</h4><ul>';
        items.forEach(i => {
          const label = skuMap[i.sku] || i.sku || 'Unknown';
          html += `<li>${label} (${i.sku}): ${i.quantity} seats</li>`;
        });
        html += '</ul>';
      } else {
        html += '<p>No items found.</p>';
      }

      detailModal.querySelector('.llmsgaa-modal-body').innerHTML = html;
      openModal(detailModal);
    });
  });

  // Redeem
  document.querySelectorAll('.llmsgaa-redeem-btn').forEach(el => {
    el.addEventListener('click', () => {
      redeemModal.querySelector('[name="pass_id"]').value = el.dataset.passId;
    openModal(redeemModal);
    });
  });
});

// Cart repeater for Shopify Test Shortcode
document.addEventListener('DOMContentLoaded', function () {
  document.body.addEventListener('click', function (e) {
    if (e.target.matches('.llmsgaa-add-row')) {
      e.preventDefault();
      const row = e.target.closest('tr').cloneNode(true);
      row.querySelectorAll('input, select').forEach(input => input.value = '');
      document.querySelector('#llmsgaa-assign-rows tbody')?.appendChild(row);
    }
  });
});
