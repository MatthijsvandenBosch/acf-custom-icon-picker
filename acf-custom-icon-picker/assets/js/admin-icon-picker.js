// assets/js/admin-icon-picker.js
(function(){
  let frame;
  const prefix = ACFIconPicker.prefix;
  const table  = document.getElementById('icon-picker-table');

  // Genereer de CSS-class op basis van label
  function updateClass(row) {
    const lbl   = row.querySelector('.icon-label').value;
    const slug  = lbl.trim().toLowerCase()
                    .replace(/[^a-z0-9]+/g,'-')
                    .replace(/(^-|-$)/g,'');
    const input = row.querySelector('.icon-class');
    input.value = prefix + slug;
  }

  // Reageer op input in het Label-veld
  table.addEventListener('input', function(e){
    if ( e.target.classList.contains('icon-label') ) {
      updateClass(e.target.closest('tr'));
    }
  });

  // Klik-events voor upload en verwijderen
  table.addEventListener('click', function(e){
    const target = e.target;

    // WP Media Uploader
    if ( target.classList.contains('upload-icon') ) {
      e.preventDefault();
      const row      = target.closest('tr');
      const imgField = row.querySelector('.icon-url');
      const imgTag   = row.querySelector('img');
      if ( frame ) frame.close();
      frame = wp.media({
        title: 'Selecteer of upload icon',
        button: { text: 'Kies dit icon' },
        multiple: false
      });
      frame.on('select', function(){
        const attachment = frame.state().get('selection').first().toJSON();
        const url        = encodeURI(attachment.url);
        imgField.value   = url;
        imgTag.src       = url;
      });
      frame.open();
    }

    // Rijtje verwijderen
    if ( target.classList.contains('remove-row') ) {
      e.preventDefault();
      target.closest('tr').remove();
    }
  });

  // Nieuw rijtje toevoegen
  document.getElementById('add-icon').addEventListener('click', function(e){
    e.preventDefault();
    const tbody = table.querySelector('tbody');
    const tr    = document.createElement('tr');
    tr.innerHTML =
      '<td><input type="text" class="icon-label" name="acf_icon_picker_icons[label][]" /></td>' +
      '<td><input type="text" class="icon-class" disabled /></td>' +
      '<td>' +
        '<input type="hidden" class="icon-url" name="acf_icon_picker_icons[url][]" />' +
        '<img src="" style="max-width:32px; vertical-align:middle;" /> ' +
        '<button class="button upload-icon">Upload/Select</button>' +
      '</td>' +
      '<td><button class="button remove-row">Verwijder</button></td>';
    tbody.appendChild(tr);
  });
})();
