<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Configuration\SystemConfiguration;
use App\Constants;
use App\Entity\User;
use App\Event\PageActionsEvent;
use App\Event\ThemeEvent;
use App\Event\ThemeJavascriptTranslationsEvent;
use App\Utils\Theme;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

final class ThemeExtension implements RuntimeExtensionInterface
{
    public function __construct(private EventDispatcherInterface $eventDispatcher, private TranslatorInterface $translator, private SystemConfiguration $configuration, private Theme $theme)
    {
    }

    /**
     * @param Environment $environment
     * @param string $eventName
     * @param mixed|null $payload
     * @return ThemeEvent
     */
    public function trigger(Environment $environment, string $eventName, $payload = null): ThemeEvent
    {
        /** @var AppVariable $app */
        $app = $environment->getGlobals()['app'];
        /** @var User $user */
        $user = $app->getUser();

        $themeEvent = new ThemeEvent($user, $payload);

        if ($this->eventDispatcher->hasListeners($eventName)) {
            $this->eventDispatcher->dispatch($themeEvent, $eventName);
        }

        return $themeEvent;
    }

    public function actions(User $user, string $action, string $view, array $payload = []): ThemeEvent
    {
        $themeEvent = new PageActionsEvent($user, $payload, $action, $view);

        $eventName = 'actions.' . $action;

        if ($this->eventDispatcher->hasListeners($eventName)) {
            $this->eventDispatcher->dispatch($themeEvent, $eventName);
        }

        return $themeEvent;
    }

    public function getJavascriptTranslations(): array
    {
        $event = new ThemeJavascriptTranslationsEvent();

        $this->eventDispatcher->dispatch($event);

        $all = [];
        foreach ($event->getTranslations() as $key => $translation) {
            $all[$key] = $this->translator->trans($translation[0], [], $translation[1]);
        }

        return $all;
    }

    public function getProgressbarClass(float $percent, ?bool $reverseColors = false): string
    {
        $colors = ['xl' => 'bg-red', 'l' => 'bg-warning', 'm' => 'bg-green', 's' => 'bg-green', 'e' => ''];
        if (true === $reverseColors) {
            $colors = ['s' => 'bg-red', 'm' => 'bg-warning', 'l' => 'bg-green', 'xl' => 'bg-green', 'e' => ''];
        }

        if ($percent > 90) {
            $class = $colors['xl'];
        } elseif ($percent > 70) {
            $class = $colors['l'];
        } elseif ($percent > 50) {
            $class = $colors['m'];
        } elseif ($percent > 30) {
            $class = $colors['s'];
        } else {
            $class = $colors['e'];
        }

        return $class;
    }

    public function generateTitle(?string $prefix = null, string $delimiter = ' – '): string
    {
        $title = $this->configuration->getBrandingTitle();
        if (null === $title || \strlen($title) === 0) {
            $title = Constants::SOFTWARE;
        }

        return ($prefix ?? '') . $title . $delimiter . $this->translator->trans('time_tracking', [], 'messages');
    }

    public function colorize(?string $color, ?string $identifier = null): string
    {
        return $this->theme->getColor($color, $identifier);
    }
}
