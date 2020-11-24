<?php
declare(strict_types=1);
namespace In2code\Luxletter\Domain\Repository;

use In2code\Luxletter\Domain\Model\Newsletter;
use In2code\Luxletter\Domain\Model\Queue;
use In2code\Luxletter\Domain\Model\User;
use In2code\Luxletter\Utility\DatabaseUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Class QueueRepository
 */
class QueueRepository extends AbstractRepository
{
    /**
     * @param int $limit
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findDispatchableInQueue(int $limit): QueryResultInterface
    {
        $query = $this->createQuery();
        $and = [
            $query->lessThan('datetime', time()),
            $query->equals('sent', false),
            $query->equals('newsletter.disabled', false),
            $query->equals('user.deleted', false),
            $query->equals('user.disable', false)
        ];
        $query->matching($query->logicalAnd($and));
        $query->setLimit($limit);
        $query->setOrderings(['tstamp' => QueryInterface::ORDER_ASCENDING]);
        return $query->execute();
    }

    /**
     * @param Newsletter $newsletter
     * @param bool $dispatched
     * @return QueryResultInterface
     */
    public function findAllByNewsletterAndDispatchedStatus(
        Newsletter $newsletter,
        bool $dispatched = false
    ): QueryResultInterface {
        $query = $this->createQuery();
        $and = [
            $query->equals('sent', $dispatched),
            $query->equals('newsletter', $newsletter)
        ];
        $query->matching($query->logicalAnd($and));
        return $query->execute();
    }

    /**
     * @param Newsletter $newsletter
     * @return QueryResultInterface
     */
    public function findAllByNewsletter(Newsletter $newsletter): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('newsletter', $newsletter));
        return $query->execute();
    }

    /**
     * Check if there is already a queue entry to this user with the same newsletter (don't care about sent status)
     *
     * @param User $user
     * @param Newsletter $newsletter
     * @return bool
     */
    public function isUserAndNewsletterAlreadyAddedToQueue(User $user, Newsletter $newsletter): bool
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable(Queue::TABLE_NAME);
        return (int)$queryBuilder
            ->select('uid')
            ->from(Queue::TABLE_NAME)
            ->where('newsletter=' . $newsletter->getUid() . ' and user=' . $user->getUid())
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn() > 0;
    }
}
