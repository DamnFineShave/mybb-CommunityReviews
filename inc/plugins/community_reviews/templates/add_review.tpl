<section>
    <p class="community-reviews__section-title">{$sectionTitle}</p>
    {$errors}
    <form method="post" action="{$formActionUrl}" id="review_form">
        {$reviewFormFieldsHtml}
        <div class="community-reviews__form__header">
            <button>{$buttonText}</button>
        </div>
        <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
    </form>
</section>
