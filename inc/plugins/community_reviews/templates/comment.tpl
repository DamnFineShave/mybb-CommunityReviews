<div class="community-reviews__review" id="comment{$comment['id']}">
    <div class="community-reviews__review__header">
        <div class="community-reviews__review__header__meta">
            <p class="community-reviews__review__header__meta__username">{$profileLink}</p> &middot;
            <p class="community-reviews__review__header__meta__date">{$date}</p>
        </div>
        <div class="community-reviews__controls">
            <p class="community-reviews__controls__moderation">{$reportLink}{$editLink}{$deleteLink}</p>
            <p class="community-reviews__controls__id"><a href="{$commentUrl}"><i class="fa fa-fw fa-link"></i></a></p>
        </div>
    </div>
    <div class="community-reviews__review__body">
        <div class="community-reviews__comment">
            {$commentValue}
        </div>
    </div>
</div>
