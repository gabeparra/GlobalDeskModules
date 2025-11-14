<?php

namespace Modules\ApiBridge\Http\Controllers\Api;

use App\Conversation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationsController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->isMethod('get')) {
            return $this->badRequest('Method not allowed.');
        }

        $sortField = 'conversations.created_at';
        $sortMapping = [
            'createdAt' => 'conversations.created_at',
            'updatedAt' => 'conversations.updated_at',
            'mailboxId' => 'mailbox_id',
            'number' => Conversation::numberFieldName(),
            'waitingSince' => 'last_reply_at',
        ];

        if ($request->filled('sortField') && isset($sortMapping[$request->get('sortField')])) {
            $sortField = $sortMapping[$request->get('sortField')];
        }

        $sortOrder = strtolower($request->get('sortOrder', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Conversation::select(['conversations.*'])
            ->orderBy($sortField, $sortOrder);

        $this->applyFilters($query, $request);

        $perPage = min(100, max(1, (int) $request->get('pageSize', 50)));
        $conversations = $query->paginate($perPage);

        if (count($conversations) > 1) {
            Conversation::loadCustomers($conversations);
            Conversation::loadUsers($conversations);
        }

        $extra = $this->extractEmbedOptions($request);
        $data = [];

        foreach ($conversations as $conversation) {
            $data[] = $this->formatter->format($conversation, true, '', $extra);
        }

        return $this->respondWithPagination($conversations, [
            'conversations' => $data,
        ]);
    }

    public function show(Conversation $conversation, Request $request): JsonResponse
    {
        $extra = $this->extractEmbedOptions($request);

        return $this->respond($this->formatter->format($conversation, true, '', $extra));
    }

    protected function extractEmbedOptions(Request $request): array
    {
        $extra = [
            'without_threads' => true,
        ];

        if ($request->filled('embed')) {
            $embeds = array_map('trim', explode(',', $request->get('embed')));

            if (in_array('threads', $embeds, true)) {
                $extra['without_threads'] = false;
            }

            if (in_array('timelogs', $embeds, true)) {
                $extra['include_timelogs'] = true;
            }

            if (in_array('tags', $embeds, true)) {
                $extra['include_tags'] = true;
            }
        }

        return $extra;
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($request->filled('mailboxId')) {
            $mailboxes = array_map('intval', explode(',', $request->get('mailboxId')));
            $query->whereIn('mailbox_id', $mailboxes);
        }

        if ($request->filled('customerEmail')) {
            $query->where('customer_email', $request->get('customerEmail'));
        }

        if ($request->filled('customerId')) {
            $query->where('customer_id', $request->get('customerId'));
        }

        if ($request->filled('folderId')) {
            $query->where('folder_id', $request->get('folderId'));
        }

        if ($request->filled('status')) {
            $statuses = explode(',', $request->get('status'));
            $decoded = [];
            foreach ($statuses as $status) {
                $status = trim($status);
                if (in_array($status, Conversation::$statuses, true)) {
                    $decoded[] = array_flip(Conversation::$statuses)[$status];
                }
            }
            if ($decoded) {
                $query->whereIn('status', $decoded);
            }
        }

        if ($request->filled('state') && in_array($request->get('state'), Conversation::$states, true)) {
            $query->where('conversations.state', array_flip(Conversation::$states)[$request->get('state')]);
        }

        if ($request->filled('type') && in_array($request->get('type'), Conversation::$types, true)) {
            $query->where('type', array_flip(Conversation::$types)[$request->get('type')]);
        }

        if ($request->has('assignedTo')) {
            $query->where('user_id', $request->get('assignedTo'));
        }

        if ($request->filled('updatedSince')) {
            $query->where('conversations.updated_at', '>=', self::asServerDate($request->get('updatedSince')));
        }

        if ($request->filled('createdSince')) {
            $query->where('conversations.created_at', '>=', self::asServerDate($request->get('createdSince')));
        }

        if ($request->filled('number')) {
            $query->where(Conversation::numberFieldName(), $request->get('number'));
        }

        if ($request->filled('subject')) {
            $query->where('subject', $this->likeOperator(), '%' . $request->get('subject') . '%');
            $query->where('subject', $this->likeOperator(), '%' . $request->get('subject') . '%');
        }

        if ($request->has('createdByUserId')) {
            $query->where('created_by_user_id', $request->get('createdByUserId'));
        }

        if ($request->has('createdByCustomerId')) {
            $query->where('created_by_customer_id', $request->get('createdByCustomerId'));
        }

        if ($request->filled('tag') && \Module::isActive('tags')) {
            $tagName = \Modules\Tags\Entities\Tag::normalizeName($request->get('tag'));
            if ($tagName) {
                $tagId = (int) \Modules\Tags\Entities\Tag::where('name', $tagName)->value('id');
                $query->join('conversation_tag', function ($join) use ($tagId) {
                    $join->on('conversations.id', '=', 'conversation_tag.conversation_id')
                        ->where('conversation_tag.tag_id', $tagId);
                });
            }
        }
    }

    protected static function asServerDate(string $utcString)
    {
        try {
            return Carbon::parse($utcString, 'UTC')
                ->setTimezone(config('app.timezone'))
                ->toDateTimeString();
        } catch (\Throwable $e) {
            return $utcString;
        }
    }

    protected function likeOperator(): string
    {
        return $this->isPgSql() ? 'ilike' : 'like';
    }

    protected function isPgSql(): bool
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        return $driver === 'pgsql';
    }
}


