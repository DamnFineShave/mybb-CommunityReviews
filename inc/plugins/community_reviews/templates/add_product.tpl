<section>
    <p class="community-reviews__section-title">{$sectionTitle}</p>
    {$errors}
    <form method="post" action="{$formActionUrl}">
        <div class="community-reviews__form__field">
            <p class="community-reviews__form__field__title">{$lang->community_reviews_product_name}</p>
            <input type="text" class="textbox" name="name" value="{$nameValue}" />
        </div>
        {$reviewFormFieldsHtml}
        <div class="community-reviews__form__header">
            <button>{$buttonText}</button>
        </div>
        <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
    </form>
</section>
