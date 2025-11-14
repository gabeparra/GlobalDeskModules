<?php

namespace Modules\ApiBridge\Services;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Email;
use App\Folder;
use App\Mailbox;
use App\Thread;
use App\User;
use Carbon\Carbon;

class PayloadFormatter
{
    public function formatList($items): array
    {
        $result = [];

        foreach ($items ?? [] as $item) {
            $result[] = $this->format($item);
        }

        return $result;
    }

    /**
     * @param mixed $entity
     * @param bool $full
     * @param string $entityType
     * @param array $extraData
     * @return mixed
     */
    public function format($entity, bool $full = true, string $entityType = '', array $extraData = [])
    {
        if (!$entity) {
            return null;
        }

        if (is_array($entity)) {
            return $entity;
        }

        if ($entity instanceof Conversation) {
            return $this->formatConversation($entity, $extraData);
        }

        if ($entity instanceof Thread) {
            return $this->formatThread($entity);
        }

        if ($entity instanceof Attachment) {
            return [
                'id' => $entity->id,
                'fileName' => $entity->file_name,
                'fileUrl' => $entity->url(),
                'mimeType' => $entity->mime_type,
                'size' => $entity->size,
            ];
        }

        if ($entity instanceof User) {
            return $full ? $this->formatUser($entity) : $this->formatUserSummary($entity);
        }

        if ($entity instanceof Customer) {
            return $full ? $this->formatCustomer($entity) : $this->formatCustomerSummary($entity);
        }

        if ($entity instanceof Mailbox) {
            return [
                'id' => $entity->id,
                'name' => $entity->name,
                'email' => $entity->email,
                'createdAt' => $this->formatDate($entity->created_at),
                'updatedAt' => $this->formatDate($entity->updated_at),
            ];
        }

        if ($entity instanceof Folder) {
            return [
                'id' => $entity->id,
                'name' => $entity->getTypeName(),
                'type' => $entity->type,
                'userId' => $entity->user_id,
                'totalCount' => $entity->total_count,
                'activeCount' => $entity->active_count,
                'meta' => $entity->meta,
            ];
        }

        if ($entity instanceof Email) {
            return [
                'id' => $entity->id,
                'value' => $entity->email,
                'type' => Email::$types[$entity->type] ?? '',
            ];
        }

        if ($entity instanceof \Modules\ApiBridge\Entities\Webhook) {
            return [
                'id' => $entity->id,
                'url' => $entity->url,
                'events' => $entity->events,
                'lastRunTime' => $this->formatDate($entity->last_run_time),
                'lastRunError' => (string) $entity->last_run_error,
                'mailboxes' => $entity->mailboxes ?? [],
            ];
        }

        if ($entity instanceof Carbon) {
            return $this->formatDate($entity);
        }

        if ($entityType === 'address') {
            return [
                'city' => $entity->city,
                'state' => $entity->state,
                'zip' => $entity->zip,
                'country' => $entity->country,
                'address' => $entity->address,
            ];
        }

        if (\Module::isActive('customfields') && $entity instanceof \CustomField) {
            return $this->formatCustomField($entity, $extraData);
        }

        if (\Module::isActive('crm') && $entity instanceof \CustomerField) {
            return $this->formatCustomerField($entity, $extraData);
        }

        if (\Module::isActive('timetracking') && $entity instanceof \Modules\TimeTracking\Entities\Timelog) {
            return $this->formatTimeLog($entity, $full);
        }

        if (\Module::isActive('tags') && $entity instanceof \Tag) {
            return $this->formatTag($entity, $full);
        }

        return null;
    }

    protected function formatConversation(Conversation $conversation, array $extraData = []): array
    {
        $threads = [];
        $formatter = $this;

        if (empty($extraData['without_threads'])) {
            foreach ($conversation->getThreads() as $thread) {
                $threads[] = $formatter->format($thread);
            }
        }

        $embedded = [
            'threads' => $threads,
        ];

        if (!empty($extraData['include_timelogs']) && \Module::isActive('timetracking')) {
            $timelogs = \Modules\TimeTracking\Entities\Timelog::where('conversation_id', $conversation->id)
                ->orderBy('id', 'desc')
                ->get();

            $embedded['timelogs'] = [];
            foreach ($timelogs as $timelog) {
                $embedded['timelogs'][] = $formatter->format($timelog, false);
            }
        }

        if (!empty($extraData['include_tags']) && \Module::isActive('tags')) {
            $tags = \Tag::conversationTags($conversation);

            $embedded['tags'] = [];
            foreach ($tags as $tag) {
                $embedded['tags'][] = $formatter->format($tag, false);
            }
        }

        $result = [
            'id' => $conversation->id,
            'number' => $conversation->number,
            'threadsCount' => (int) $conversation->threads_count,
            'type' => Conversation::$types[$conversation->type] ?? 'email',
            'folderId' => $conversation->folder_id,
            'status' => Conversation::$statuses[$conversation->status] ?? 'active',
            'state' => Conversation::$states[$conversation->state] ?? 'published',
            'subject' => (string) $conversation->subject,
            'preview' => $conversation->preview,
            'mailboxId' => $conversation->mailbox_id,
            'assignee' => ($conversation->user_id && $conversation->user ? $this->format($conversation->user, false) : null),
            'createdBy' => ($conversation->source_via == Conversation::PERSON_USER
                ? $this->format($conversation->created_by_user, false)
                : $this->format($conversation->created_by_customer, false)),
            'createdAt' => $this->formatDate($conversation->created_at),
            'updatedAt' => $this->formatDate($conversation->updated_at),
            'closedBy' => ($conversation->status == Conversation::STATUS_CLOSED ? $conversation->closed_by_user_id : null),
            'closedByUser' => ($conversation->status == Conversation::STATUS_CLOSED && $conversation->closed_by_user_id
                ? $this->format($conversation->closed_by_user, false)
                : null),
            'closedAt' => $this->formatDate($conversation->closed_at),
            'userUpdatedAt' => $this->formatDate($conversation->user_updated_at),
            'customerWaitingSince' => [
                'time' => $this->formatDate($conversation->last_reply_at),
                'friendly' => $conversation->getWaitingSince(),
                'latestReplyFrom' => Conversation::$persons[$conversation->last_reply_from] ?? '',
            ],
            'source' => [
                'type' => Conversation::$source_types[$conversation->source_type] ?? '',
                'via' => Conversation::$persons[$conversation->source_via] ?? '',
            ],
            'cc' => $conversation->getCcArray(),
            'bcc' => $conversation->getBccArray(),
            'customer' => ($conversation->customer_id && $conversation->customer
                ? $this->format($conversation->customer, false)
                : null),
            '_embedded' => $embedded,
        ];

        if (\Module::isActive('customfields')) {
            $customFields = $extraData['custom_fields'] ?? [];

            if (empty($customFields)) {
                $customFields = \CustomField::getCustomFieldsWithValues($conversation->mailbox_id, $conversation->id);
            }

            if (!empty($customFields)) {
                foreach ($customFields as $field) {
                    $result['customFields'][] = $this->format($field);
                }
            }
        }

        return $result;
    }

    protected function formatThread(Thread $thread): array
    {
        return [
            'id' => $thread->id,
            'type' => $thread->getTypeName(),
            'status' => Thread::$statuses[$thread->status] ?? 'active',
            'state' => $thread->getStateName(),
            'action' => [
                'type' => $thread->getActionTypeName(),
                'text' => $thread->conversation ? strip_tags($thread->getActionDescription($thread->conversation->number, false)) : '',
                'associatedEntities' => [],
            ],
            'body' => $thread->body,
            'source' => [
                'type' => Thread::$source_types[$thread->source_type] ?? '',
                'via' => Thread::$persons[$thread->source_via] ?? '',
            ],
            'customer' => ($thread->customer_id && $thread->customer ? $this->format($thread->customer, false) : null),
            'createdBy' => ($thread->source_via == Thread::PERSON_USER
                ? $this->format($thread->created_by_user, false)
                : $this->format($thread->created_by_customer, false)),
            'assignedTo' => ($thread->user_id && $thread->user ? $this->format($thread->user, false) : null),
            'to' => $thread->getToArray(),
            'cc' => $thread->getCcArray(),
            'bcc' => $thread->getBccArray(),
            'createdAt' => $this->formatDate($thread->created_at),
            'openedAt' => $this->formatDate($thread->opened_at),
            '_embedded' => [
                'attachments' => $thread->has_attachments ? $this->formatList($thread->attachments) : [],
            ],
        ];
    }

    protected function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'email' => $user->email,
            'role' => User::$roles[$user->role] ?? User::ROLE_USER,
            'alternateEmails' => $user->emails,
            'jobTitle' => $user->job_title,
            'phone' => $user->phone,
            'timezone' => $user->timezone,
            'photoUrl' => $user->getPhotoUrl(false),
            'language' => $user->locale,
            'createdAt' => $this->formatDate($user->created_at),
            'updatedAt' => $this->formatDate($user->updated_at),
        ];
    }

    protected function formatUserSummary(User $user): array
    {
        return [
            'id' => $user->id,
            'type' => 'user',
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'photoUrl' => $user->getPhotoUrl(false),
            'email' => $user->email,
        ];
    }

    protected function formatCustomer(Customer $customer): array
    {
        $emails = [];
        foreach ($customer->emails as $email) {
            $emails[] = $this->format($email, false);
        }

        $phones = [];
        foreach ($customer->getPhones() as $phone) {
            $phones[] = [
                'id' => 0,
                'value' => $phone['value'] ?? '',
                'type' => Customer::$phone_types[$phone['type']] ?? 'other',
            ];
        }

        $socialProfiles = [];
        foreach ($customer->getSocialProfiles() as $profile) {
            $socialProfiles[] = [
                'id' => 0,
                'value' => $profile['value'] ?? '',
                'type' => Customer::$social_types[$profile['type']] ?? 'other',
            ];
        }

        $websites = [];
        foreach ($customer->getWebsites() as $website) {
            $websites[] = [
                'id' => 0,
                'value' => $website,
            ];
        }

        $result = [
            'id' => $customer->id,
            'firstName' => $customer->first_name,
            'lastName' => $customer->last_name,
            'jobTitle' => $customer->job_title,
            'company' => $customer->company,
            'photoType' => Customer::$photo_types[$customer->photo_type ?? Customer::PHOTO_TYPE_UKNOWN] ?? 'unknown',
            'photoUrl' => $customer->getPhotoUrl(false),
            'createdAt' => $this->formatDate($customer->created_at),
            'updatedAt' => $this->formatDate($customer->updated_at),
            'notes' => $customer->notes,
            '_embedded' => [
                'emails' => $emails,
                'phones' => $phones,
                'social_profiles' => $socialProfiles,
                'websites' => $websites,
                'address' => $this->format($customer, false, 'address'),
            ],
        ];

        if (\Module::isActive('crm')) {
            $fields = \CustomerField::getCustomerFieldsWithValues($customer->id);
            foreach ($fields as $field) {
                $result['customerFields'][] = $this->format($field);
            }
        }

        return $result;
    }

    protected function formatCustomerSummary(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'type' => 'customer',
            'firstName' => $customer->first_name,
            'lastName' => $customer->last_name,
            'photoUrl' => $customer->getPhotoUrl(false),
            'email' => $customer->getMainEmail(),
        ];
    }

    protected function formatCustomField(\CustomField $field, array $extraData): array
    {
        if (empty($extraData['customfield_structure'])) {
            $text = '';
            if ($field->type == \CustomField::TYPE_DROPDOWN) {
                if (!empty($field->options) && !empty($field->options[$field->value])) {
                    $text = $field->options[$field->value];
                }
            }

            return [
                'id' => $field->id,
                'name' => $field->name,
                'value' => (string) $field->value,
                'text' => $text,
            ];
        }

        return [
            'id' => $field->id,
            'name' => $field->name,
            'type' => str_replace(' ', '', strtolower(\CustomField::$types[$field->type ?? \CustomField::TYPE_SINGLE_LINE])),
            'options' => $field->options,
            'required' => (bool) $field->required,
            'sortOrder' => $field->sort_order,
        ];
    }

    protected function formatCustomerField(\CustomerField $field, array $extraData): array
    {
        if (empty($extraData['customfield_structure'])) {
            $text = '';
            if ($field->type == \CustomerField::TYPE_DROPDOWN) {
                if (!empty($field->options) && !empty($field->options[$field->value])) {
                    $text = $field->options[$field->value];
                }
            }

            return [
                'id' => $field->id,
                'name' => $field->name,
                'value' => (string) $field->value,
                'text' => $text,
            ];
        }

        return [
            'id' => $field->id,
            'name' => $field->name,
            'type' => str_replace(' ', '', strtolower(\CustomerField::$types[$field->type ?? \CustomerField::TYPE_SINGLE_LINE])),
            'options' => $field->options,
            'required' => (bool) $field->required,
            'sortOrder' => $field->sort_order,
        ];
    }

    protected function formatTimeLog(\Modules\TimeTracking\Entities\Timelog $timelog, bool $full): array
    {
        $result = [
            'id' => $timelog->id,
            'conversationStatus' => Conversation::$statuses[$timelog->conversation_status] ?? 'active',
            'userId' => $timelog->user_id,
            'timeSpent' => $timelog->time_spent,
            'paused' => (bool) $timelog->paused,
            'finished' => (bool) $timelog->finished,
            'createdAt' => $this->formatDate($timelog->created_at),
            'updatedAt' => $this->formatDate($timelog->updated_at),
        ];

        if ($full) {
            $result['conversationId'] = $timelog->conversation_id;
        }

        return $result;
    }

    protected function formatTag(\Tag $tag, bool $full): array
    {
        $result = [
            'id' => $tag->id,
            'name' => $tag->name,
        ];

        if ($full) {
            $result['counter'] = $tag->counter;
            $result['color'] = $tag->color;
        }

        return $result;
    }

    protected function formatDate(?Carbon $date): ?string
    {
        return $date ? $date->copy()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z') : null;
    }
}



