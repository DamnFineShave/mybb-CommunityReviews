<section>
    <p class="community-reviews__section-title">{$sectionTitle}</p>
    {$errors}
    {$messages}
    <form method="post" action="{$formActionUrl}">
        <div class="community-reviews__form__field">
            <p class="community-reviews__form__field__title">{$lang->community_reviews_comment}</p>
            <textarea class="textbox" name="comment">{$commentValue}</textarea>
        </div>
        <div class="community-reviews__form__header">
            <button>{$buttonText}</button>
        </div>
        <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
    </form>
</section>
