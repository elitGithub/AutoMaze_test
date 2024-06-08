<?php

namespace AutoMaze\Modules\Comments;

use Core\Model;
use Core\Storm;

class CommentsModel extends Model
{
    public string $tableName = 'comments';
    public array  $rules     = [
        'content' => [self::RULE_REQUIRED],
        'bug_id'  => [self::RULE_REQUIRED],
    ];


    public function addCommentToBug($bugId, $content, $created_by)
    {
        $tables = Storm::getStorm()->db->get_tables();
        if (!in_array($this->tableName, $tables)) {
            $this->createCommentsTable();
        }
        $this->loadAttributes();

        $this->attributes['content'] = $content;
        if ($this->validate()) {
            $query = "INSERT INTO $this->tableName (`content`, `created_by`, `bug_id`) VALUES (?, ?, ?)";
            Storm::getStorm()->db->pquery($query, [$content, $created_by, $bugId]);
            $lastInsertId = Storm::getStorm()->db->getLastInsertID();

            $newComment = [
                'id'         => $lastInsertId,
                'content'    => $content,
                'created_by' => $created_by,
                'bug_id'     => $bugId,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            Storm::getStorm()->emitEvent('commentAdded', $newComment);

            return $lastInsertId;
        }

        return null;
    }


    public function params(): array
    {
        return $this->params;
    }

    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        return $this->rules;
    }

    public function getDisplayName(): string
    {
        return 'Comments';
    }

    private function createCommentsTable()
    {
        $query = 'CREATE TABLE `comments` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `content` TEXT NOT NULL,
    `created_by` VARCHAR(255) NOT NULL,
    `bug_id` VARCHAR(255) NOT NULL,
     FOREIGN KEY (`bug_id`) REFERENCES `bug_reports`(`id`) ON DELETE CASCADE);
CREATE INDEX `idx_bug_id` ON `comments` (`bug_id`);
CREATE INDEX `idx_created_by` ON `comments` (`created_by`);';
        Storm::getStorm()->db->query($query);
    }
}