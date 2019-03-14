<?php
/**
 * This file is part of the eZ LegacyBridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Security\Voter;

use Closure;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;

class VoterDecorator implements VoterInterface
{
    private $innerVoter;

    private $legacyKernelClosure;

    public function __construct(
        VoterInterface $innerVoter,
        Closure $legacyKernelClosure
    ) {
        $this->innerVoter = $innerVoter;
        $this->legacyKernelClosure = $legacyKernelClosure;
    }

    public function supportsAttribute($attribute)
    {
        return $attribute instanceof AuthorizationAttribute;
    }

    public function supportsClass($class)
    {
        return true;
    }

    /**
     * Decorates the built in eZ kernel voters to allow eZ Publish legacy
     * to vote on modules/functions available only in legacy kernel.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param mixed $object
     * @param array $attributes
     *
     * @return int
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        try {
            return $this->innerVoter->vote($token, $object, $attributes);
        } catch (InvalidArgumentException $e) {
            $legacyResult = $this->voteLegacy($token, $object, $attributes);
            if ($legacyResult === static::ACCESS_ABSTAIN) {
                throw $e;
            }

            return $legacyResult;
        }
    }

    private function voteLegacy(TokenInterface $token, $object, array $attributes)
    {
        $legacyKernel = \call_user_func($this->legacyKernelClosure);
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $result = $legacyKernel->runCallback(
                static function () use ($attribute) {
                    $currentUser = eZUser::currentUser();

                    return $currentUser->hasAccessTo($attribute->module, $attribute->function);
                },
                false
            );

            if ($result['accessWord'] === 'no') {
                return static::ACCESS_DENIED;
            }

            return static::ACCESS_GRANTED;
        }

        return static::ACCESS_ABSTAIN;
    }
}
