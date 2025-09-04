jQuery(document).ready(function($) {
    var carrierIndex = $('#jutso-carriers-table tbody tr').length;

    // Add new carrier row
    $('#jutso-add-carrier').on('click', function() {
        var newRow = '<tr>' +
            '<td class="jutso-carrier-key"><input type="text" name="jutso_st_carriers[new_' + carrierIndex + '][key]" value="" placeholder="carrier_key" /></td>' +
            '<td class="jutso-carrier-name"><input type="text" name="jutso_st_carriers[new_' + carrierIndex + '][name]" value="" placeholder="Carrier Name" /></td>' +
            '<td class="jutso-carrier-url"><input type="text" name="jutso_st_carriers[new_' + carrierIndex + '][url]" value="" placeholder="https://track.example.com/?tracking={tracking_number}" /></td>' +
            '<td class="jutso-carrier-actions"><button type="button" class="button jutso-remove-carrier">Remove</button></td>' +
            '</tr>';
        
        $('#jutso-carriers-table tbody').append(newRow);
        carrierIndex++;
    });

    // Remove carrier row
    $(document).on('click', '.jutso-remove-carrier', function() {
        $(this).closest('tr').remove();
        updateDefaultCarrierOptions();
    });

    // Update default carrier dropdown when carriers change
    function updateDefaultCarrierOptions() {
        var currentDefault = $('#jutso_st_default_carrier').val();
        var options = '<option value="">— No default —</option>';
        
        $('#jutso-carriers-table tbody tr').each(function() {
            var keyInput = $(this).find('input[name*="[key]"]').val();
            var nameInput = $(this).find('input[name*="[name]"]').val();
            
            if (keyInput && nameInput) {
                options += '<option value="' + keyInput + '">' + nameInput + '</option>';
            }
        });
        
        $('#jutso_st_default_carrier').html(options).val(currentDefault);
    }

    // Update default carrier options when carrier fields change
    $(document).on('change', '#jutso-carriers-table input[name*="[key]"], #jutso-carriers-table input[name*="[name]"]', function() {
        updateDefaultCarrierOptions();
    });
});