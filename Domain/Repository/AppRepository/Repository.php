<?php

/*
 * This file is part of the Apisearch Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Apisearch\Server\Domain\Repository\AppRepository;

use Apisearch\App\AppRepository as BaseRepository;
use Apisearch\Config\Config;
use Apisearch\Exception\ResourceExistsException;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Exception\TransportableException;
use Apisearch\Model\Index;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryWithCredentials;
use Apisearch\Server\Domain\Repository\WithRepositories;
use Apisearch\Server\Domain\Token\TokenProviders;

/**
 * Class Repository.
 */
class Repository extends RepositoryWithCredentials implements BaseRepository
{
    use WithRepositories;

    /**
     * @var TokenProviders
     *
     * Token providers
     */
    private $tokenProviders;

    /**
     * Repository constructor.
     *
     * @param TokenProviders $tokenProviders
     */
    public function __construct(TokenProviders $tokenProviders)
    {
        $this->tokenProviders = $tokenProviders;
    }

    /**
     * Add token.
     *
     * @param Token $token
     */
    public function addToken(Token $token)
    {
        $tokenRepository = $this->getRepository(TokenRepository::class);
        if ($tokenRepository instanceof TokenRepository) {
            $tokenRepository->addToken($token);
        }
    }

    /**
     * Delete token.
     *
     * @param TokenUUID $tokenUUID
     */
    public function deleteToken(TokenUUID $tokenUUID)
    {
        $tokenRepository = $this->getRepository(TokenRepository::class);
        if ($tokenRepository instanceof TokenRepository) {
            $tokenRepository->deleteToken($tokenUUID);
        }
    }

    /**
     * Get tokens.
     *
     * @return Token[]
     */
    public function getTokens(): array
    {
        return $this
            ->tokenProviders
            ->getTokensByAppUUID($this->getAppUUID());
    }

    /**
     * Delete all tokens.
     */
    public function deleteTokens()
    {
        $tokenRepository = $this->getRepository(TokenRepository::class);
        if ($tokenRepository instanceof TokenRepository) {
            $tokenRepository->deleteTokens();
        }
    }

    /**
     * Get indices.
     *
     * @return Index[]
     */
    public function getIndices(): array
    {
        return $this
            ->getRepository(IndexRepository::class)
            ->getIndices();
    }

    /**
     * Create an index.
     *
     * @param IndexUUID $indexUUID
     * @param Config    $config
     *
     * @throws ResourceExistsException
     */
    public function createIndex(
        IndexUUID $indexUUID,
        Config $config
    ) {
        return $this
            ->getRepository(IndexRepository::class)
            ->createIndex(
                $indexUUID,
                $config
            );
    }

    /**
     * Delete an index.
     *
     * @param IndexUUID $indexUUID
     *
     * @throws ResourceNotAvailableException
     */
    public function deleteIndex(IndexUUID $indexUUID)
    {
        $this
            ->getRepository(IndexRepository::class)
            ->deleteIndex($indexUUID);
    }

    /**
     * Reset the index.
     *
     * @param IndexUUID $indexUUID
     *
     * @throws ResourceNotAvailableException
     */
    public function resetIndex(IndexUUID $indexUUID)
    {
        $this
            ->getRepository(IndexRepository::class)
            ->resetIndex($indexUUID);
    }

    /**
     * Checks the index.
     *
     * @param IndexUUID $indexUUID
     *
     * @return bool
     */
    public function checkIndex(IndexUUID $indexUUID): bool
    {
        try {
            $result = $this
                ->getRepository(IndexRepository::class)
                ->isIndexOK($indexUUID);
        } catch (TransportableException $exception) {
            return false;
        }

        return $result;
    }

    /**
     * Config the index.
     *
     * @param IndexUUID $indexUUID
     * @param Config    $config
     *
     * @throws ResourceNotAvailableException
     */
    public function configureIndex(
        IndexUUID $indexUUID,
        Config $config
    ) {
        $this
            ->getRepository(IndexRepository::class)
            ->configureIndex(
                $indexUUID,
                $config
            );
    }
}
