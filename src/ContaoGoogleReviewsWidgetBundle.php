<?php

namespace BairamovWeba11y\ContaoGoogleReviewsWidget;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoGoogleReviewsWidgetBundle extends Bundle
{
  public function getPath(): string
  {
    return \dirname(__DIR__);
  }
}