<div class="community-reviews__form__field">
    <p class="community-reviews__form__field__title">{$lang->community_reviews_product_url}</p>
    <input type="text" class="textbox" name="url" value="{$productUrlValue}" />
</div>
<div class="community-reviews__form__field">
    <p class="community-reviews__form__field__title">{$lang->community_reviews_product_price}</p>
    <input type="text" class="textbox" name="price" value="{$productPriceValue}" />
</div>
<div class="community-reviews__form__field">
    <p class="community-reviews__form__field__title">{$lang->community_reviews_product_photos}</p>
        <p class="community-reviews__form__field__description">{$lang->community_reviews_product_photos_description}</p>
    <div>
        {$reviewPhotos}
    </div>
    <br />
    <input type="file" id="review_photos" multiple accept="image/*" />
</div>
<div class="community-reviews__form__field">
    <p class="community-reviews__form__field__title">{$lang->community_reviews_product_merchants}</p>
    <p class="community-reviews__form__field__description">{$lang->community_reviews_product_merchants_description}</p>
    <br />
    <input type="text" class="textbox" name="merchants" value="{$productMerchantsValue}" data-value="{$productMerchantsValue}" style="min-width:150px" />
</div>

<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css">
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js"></script>

<script>
$(function() {
    communityReviews.handleUpload($('#review_photos'));
});

if(use_xmlhttprequest == "1") {
    MyBB.select2();
    $('input[name="merchants"]').select2({
        placeholder: "{$lang->search_user}",
        minimumInputLength: 2,
        multiple: true,
        ajax: {
            url: 'xmlhttp.php?action=community_reviews_get_merchants',
            dataType: 'json',
            data: function (term, page) {
                return {
                    query: term,
                };
            },
            results: function (data, page) {
                return { results: data };
            }
        },
        initSelection: function(element, callback) {
            var value = $(element).val();
            if (value !== "") {
                callback({
                    id: value,
                    text: value
                });
            }
        },
    });

    if ($('input[name="merchants"]').attr('data-value')) {
        var values = $('input[name="merchants"]').attr('data-value').split(',');
        if (values.length) {
            var newData = [];
            values.forEach(function(e) {
                newData.push({
                    id: e,
                    text: e,
                });
            });
            $('input[name="merchants"]').select2('data', newData);
        }
    }
}
</script>
