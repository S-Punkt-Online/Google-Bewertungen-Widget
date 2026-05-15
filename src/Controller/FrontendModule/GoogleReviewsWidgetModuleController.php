<?php

namespace SPunktOnline\ContaoGoogleReviewsWidget\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\Template;
use SPunktOnline\ContaoGoogleReviewsWidget\Service\GoogleReviewsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(
    type: 'google_reviews_widget',
    category: 'miscellaneous',
    template: 'mod_google_reviews_widget'
)]
class GoogleReviewsWidgetModuleController extends AbstractFrontendModuleController
{
    public function __construct(
        private readonly GoogleReviewsService $googleReviewsService
    ) {
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $GLOBALS['TL_CSS'][] = 'bundles/contaogooglereviewswidget/css/google-reviews-widget.css|static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaogooglereviewswidget/js/google-reviews-widget.js|static';

        $reviews = $this->googleReviewsService->getReviews(
            $model->google_api_key,
            $model->google_place_id
        );

        $formattedRating = $reviews['formatted_rating'] ?? '0.0';

        $template->formattedRating = $formattedRating;
        $template->userRatingsTotal = $reviews['user_ratings_total'] ?? 0;
        $template->starCount = round((float) str_replace(',', '.', $formattedRating));

        $template->businessName = $model->google_business_name;
        $template->businessUrl = $model->google_business_url;
        $template->googleReviewUrl = $model->google_review_url;

        return $template->getResponse();
    }
}
