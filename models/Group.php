<?php
/**
 * This file contains functionality relating to the participants of a group message
 *
 * @package    BZiON\Models
 * @license    https://github.com/allejo/bzion/blob/master/LICENSE.md GNU General Public License Version 3
 */

/**
 * A discussion (group of messages)
 * @package    BZiON\Models
 */
class Group extends UrlModel implements NamedModel
{
    /**
     * The subject of the group
     * @var string
     */
    protected $subject;

    /**
     * The time of the last message to the group
     * @var TimeDate
     */
    protected $last_activity;

    /**
     * The id of the creator of the group
     * @var int
     */
    protected $creator;

    /**
     * The status of the group
     *
     * Can be 'active', 'disabled', 'deleted' or 'reported'
     * @var string
     */
    protected $status;

    /**
     * The name of the database table used for queries
     */
    const TABLE = "groups";

    /**
     * {@inheritDoc}
     */
    protected function assignResult($group)
    {
        $this->subject = $group['subject'];
        $this->last_activity = TimeDate::fromMysql($group['last_activity']);
        $this->creator = $group['creator'];
        $this->status = $group['status'];
    }

    /**
     * Get the subject of the discussion
     *
     * @return string
     **/
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get the creator of the discussion
     *
     * @return Player
     */
    public function getCreator()
    {
        return Player::get($this->creator);
    }

    /**
     * Determine whether a player is the one who created the message group
     *
     * @param  int  $id The ID of the player to test for
     * @return bool
     */
    public function isCreator($id)
    {
        return ($this->creator == $id);
    }

    /**
     * Get the time when the group was most recently active
     *
     * @param  bool            $human True to output the last activity in a human-readable string, false to return a TimeDate object
     * @return string|TimeDate
     */
    public function getLastActivity($human = true)
    {
        if ($human) {
            return $this->last_activity->diffForHumans();
        } else {
            return $this->last_activity;
        }
    }

    /**
     * Update the group's last activity timestamp
     *
     * @return void
     */
    public function updateLastActivity()
    {
        $this->last_activity = TimeDate::now();
        $this->update('last_activity', $this->last_activity->toMysql(), 's');
    }

    /**
     * Update the group's subject
     *
     * @param  string $subject The new subject
     * @return self
     */
    public function setSubject($subject)
    {
        return $this->updateProperty($this->subject, 'subject', $subject, 's');
    }

    /**
     * Get the last message of the group
     *
     * @return Message
     */
    public function getLastMessage()
    {
        $ids = self::fetchIdsFrom('group_to', array($this->id), 'i', false, 'ORDER BY id DESC LIMIT 0,1', 'messages');

        if (!isset($ids[0])) {
            return Message::invalid();
        }

        return Message::get($ids[0]);
    }

    /**
     * Find whether the last message in the group has been read by a player
     *
     * @param  int     $playerId The ID of the player
     * @return boolean
     */
    public function isReadBy($playerId)
    {
        $query = $this->db->query("SELECT `read` FROM `player_groups` WHERE `player` = ? AND `group` = ?",
            'ii', array($playerId, $this->id));

        return ($query[0]['read'] == 1);
    }

    /**
     * Mark the last message in the group as having been read by a player
     *
     * @param  int  $playerId The ID of the player
     * @return void
     */
    public function markReadBy($playerId)
    {
        $this->db->query(
            "UPDATE `player_groups` SET `read` = 1 WHERE `player` = ? AND `group` = ? AND `read` = 0",
            'ii', array($playerId, $this->id)
        );
    }

    /**
     * Mark the last message in the group as unread by the group's members
     *
     * @param  int  $except The ID of a player to exclude
     * @return void
     */
    public function markUnread($except)
    {
        $this->db->query(
            "UPDATE `player_groups` SET `read` = 0 WHERE `group` = ? AND `player` != ?",
            'ii',
            array($this->id, $except)
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getRouteName($action = 'show')
    {
        return "message_discussion_$action";
    }

    /**
     * {@inheritDoc}
     */
    public static function getParamName()
    {
        return "discussion";
    }

    /**
     * {@inheritDoc}
     */
    public static function getActiveStatuses()
    {
        return array('active', 'reported');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getSubject();
    }

    /**
     * Get a list containing each member of the group
     * @param  int|null $hide The ID of a player to ignore
     * @return Model[]  An array of players and teams
     */
    public function getMembers($hide = null)
    {
        $members = Player::arrayIdToModel($this->getPlayerIds($hide, true));
        usort($members, Player::getAlphabeticalSort());

        $teams = Team::arrayIdToModel($this->getTeamIds());
        usort($teams, Team::getAlphabeticalSort());

        return array_merge($members, $teams);
    }

    /**
     * Get a list containing the IDs of each member player of the group
     * @param  int|null  $hide     The ID of a player to ignore
     * @param  boolean   $distinct Whether to only return players who were
     *                             specifically invited to the conversation, and
     *                             are not participating only as members of a team
     * @return integer[] An array of player IDs
     */
    public function getPlayerIds($hide = null, $distinct = false)
    {
        $additional_query = "WHERE `group` = ?";
        $types = "i";
        $params = array($this->id);

        if ($hide) {
            $additional_query .= " AND `player` != ?";
            $types .= "i";
            $params[] = $hide;
        }

        if ($distinct) {
            $additional_query .= " AND `distinct` = 1";
        }

        return parent::fetchIds($additional_query, $types, $params, "player_groups", "player");
    }

    /**
     * Get a list containing the IDs of each member team of the group
     *
     * @return integer[] An array of team IDs
     */
    public function getTeamIds()
    {
        return parent::fetchIds("WHERE `group` = ?", "i", $this->id, "team_groups", "team");
    }

    /**
     * Create a new message group
     *
     * @todo Support team members
     *
     * @param  string $subject   The subject of the group
     * @param  int    $creatorId The ID of the player who created the group
     * @param  array  $members   A list of IDs representing the group's members
     * @return Group  An object that represents the created group
     */
    public static function createGroup($subject, $creatorId, $members = array())
    {
        $group = self::create(array(
            'subject' => $subject,
            'creator' => $creatorId,
            'status'  => "active",
        ), 'sis', 'last_activity');

        foreach ($members as $mid) {
            parent::create(array(
                'player' => $mid,
                'group'  => $group->getId(),
            ), 'ii', null, 'player_groups');
        }

        return $group;
    }

    /**
     * Send a new message to the group's members
     * @param  Player  $from    The sender
     * @param  string  $message The body of the message
     * @param  string  $status  The status of the message - can be 'sent', 'hidden', 'deleted' or 'reported'
     * @return Message An object that represents the sent message
     */
    public function sendMessage($from, $message, $status = 'sent')
    {
        $message = Message::sendMessage($this->getId(), $from->getId(), $message, $status);

        $this->updateLastActivity();

        return $message;
    }

    /**
     * Get all the groups in the database a player belongs to that are not disabled or deleted
     * @todo Move this to the Player class
     * @param  int     $id The id of the player whose groups are being retrieved
     * @return Group[] An array of group objects
     */
    public static function getGroups($id)
    {
        $additional_query = "LEFT JOIN groups ON player_groups.group=groups.id
                             WHERE player_groups.player = ? AND groups.status
                             NOT IN (?, ?) ORDER BY last_activity DESC";
        $params = array($id, "disabled", "deleted");

        return self::arrayIdToModel(self::fetchIds($additional_query, "iss", $params, "player_groups", "groups.id"));
    }

    /**
     * Checks if a player or team belongs in the group
     * @param  Player|Team $member The player or team to check
     * @return bool True if the given object belongs in the group, false if they don't
     */
    public function isMember($member)
    {
        $type = ($member instanceof Player) ? 'player' : 'team';

        $result = $this->db->query("SELECT 1 FROM `{$type}_groups` WHERE `group` = ?
                                    AND `$type` = ?", "ii", array($this->id, $member->getId()));

        return count($result) > 0;
    }

    /**
     * Add a member to the discussion
     *
     * @param  Player|Team $member The member  to add
     * @return void
     */
    public function addMember($member)
    {
        if ($member instanceof Player) {
            // Mark individual players as distinct by creating or updating the
            // entry on the table
            $this->db->query(
                "INSERT INTO `player_groups` (`group`, `player`, `distinct`) VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE `distinct` = 1",
                "ii",
                array($this->getId(), $member->getId())
            );
        } elseif ($member instanceof Team) {
            // Add the team to the team_groups table...
            $this->db->query(
                "INSERT INTO `team_groups` (`group`, `team`) VALUES (?, ?)",
                "ii",
                array($this->getId(), $member->getId())
            );

            // ...and each of its members in the player_groups table as
            // non-distinct (unless they were already there)
            foreach ($member->getMembers() as $player) {
                $this->db->query(
                    "INSERT IGNORE INTO `player_groups` (`group`, `player`, `distinct`) VALUES (?, ?, 0)",
                    "ii",
                    array($this->getId(), $player->getId())
                );
            }
        }
    }

    /**
     * Remove a member from the discussion
     *
     * @todo
     *
     * @param  Player|Team $member The member to remove
     * @return void
     */
    public function removeMember($member)
    {
        if ($member instanceof Player) {
            $this->db->query("DELETE FROM `player_groups` WHERE `group` = ? AND `player` = ?", "ii", array($this->getId(), $member->getId()));
        } else {
            throw new Exception("Not implemented yet");
        }
    }

    /**
     * Find out which members of the group should receive an e-mail after a new
     * message has been sent
     *
     * @param  int   $except The ID of a player who won't receive an e-mail (e.g. message author)
     * @return int[] A player ID list
     */
    public function getWaitingForEmailIDs($except)
    {
        return $this->fetchIds(
            'LEFT JOIN players ON pg.player = players.id WHERE pg.group = ? AND pg.read = 1 AND pg.player != ?  AND players.verified = 1 AND players.receives != "nothing"',
            'ii',
            array($this->id, $except),
            'player_groups AS pg',
            'pg.player');
    }

    /**
     * Get a query builder for groups
     * @return QueryBuilder
     */
    public static function getQueryBuilder()
    {
        return new QueryBuilder('Group', array(
            'columns' => array(
                'status' => 'status'
            ),
            'name' => 'subject',
        ));
    }
}
