<section>
    <p class="community-reviews__section-title">{$lang->community_reviews_confirm_action}</p>
    <p class="community-reviews__text">{$text}</p>
    <form action="" method="post">
        <div class="community-reviews__form__options">
            <a href="javascript:window.history.back()">{$lang->community_reviews_back}</a>
            <button>{$lang->community_reviews_proceed}</button>
        </div>
        <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
    </form>
</section>
