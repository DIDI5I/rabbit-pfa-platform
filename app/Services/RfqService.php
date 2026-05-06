<?php

namespace App\Services;

use App\Core\App;
use App\Core\Database;
use App\Forms\RFQs\AcceptRfqRequest;
use App\Forms\RFQs\CreateRfqRequest;
use App\Repositories\RfqRepository;
use App\Exceptions\ValidationException;
use App\Core\Session;
use App\Core\Auth;
use App\Support\ApiResponse;
use App\Services\PurchaseLotService;
use App\Services\NotificationService;
use App\Repositories\UserRepository;

use App\Forms\RFQs\QuoteRfqRequest;

class RfqService
{
    protected RfqRepository $repository;

    public function __construct()
    {
        $this->repository = new RfqRepository();
    }

    
    public function accept(AcceptRfqRequest $request): array
    {
        return $this->acceptByData(
            $request->rfqId(),
            $request->decisionNote(),
            $request->userId()
        );
    }

    public function acceptByData(int $rfqId, ?string $decisionNote, int $userId): array
    {
        $database = App::resolve(Database::class);
        $pdo = $database->connection();

        try {
            $pdo->beginTransaction();

            $result = $this->repository->accept(
                $rfqId,
                $userId,
                $decisionNote
            );

            $rfq = $this->repository->findById($rfqId);

            $this->createPurchaseLotAfterRfqAccepted($rfq, $userId);

            $pdo->commit();

            $rfq = $this->repository->findById($rfqId);

            if ($rfq && !empty($rfq['supplier_id'])) {
                $this->notifySupplierCompany(
                    (int) $rfq['supplier_id'],
                    'RFQ_ACCEPTED_BY_OWNER',
                    'Devis accepté',
                    'Votre devis a été accepté par le propriétaire.',
                    'rfq',
                    $rfqId
                );
            }

            $this->notifyOwner(
                'PURCHASE_LOT_NEEDS_FINALIZATION',
                'Lot d’achat à finaliser',
                'Une RFQ acceptée a généré un lot d’achat à finaliser.',
                'rfq',
                $rfqId
            );

            return ApiResponse::success('RFQ accepted successfully', $result);

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function reject(AcceptRfqRequest $request): array
    {
        return $this->rejectByData(
            $request->rfqId(),
            $request->decisionNote(),
            $request->userId()
        );
    }

    public function rejectByData(int $rfqId, ?string $decisionNote, int $userId): array
    {
        $database = App::resolve(Database::class);
        $pdo = $database->connection();

        try {
            $pdo->beginTransaction();

            $result = $this->repository->reject(
                $rfqId,
                $userId,
                $decisionNote
            );

            $pdo->commit();

            $rfq = $this->repository->findById($rfqId);

            if ($rfq && !empty($rfq['supplier_id'])) {
                $this->notifySupplierCompany(
                    (int) $rfq['supplier_id'],
                    'RFQ_REJECTED_BY_OWNER',
                    'Devis rejeté',
                    'Votre devis n’a pas été retenu.',
                    'rfq',
                    $rfqId
                );
            }

            return ApiResponse::success('RFQ rejected successfully', $result);

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function expire(AcceptRfqRequest $request): array
    {
        return $this->expireByData(
            $request->rfqId(),
            $request->decisionNote(),
            $request->userId()
        );
    }

    public function expireByData(int $rfqId, ?string $decisionNote, int $userId): array
    {
        $database = App::resolve(Database::class);
        $pdo = $database->connection();

        try {
            $pdo->beginTransaction();

            $result = $this->repository->expire(
                $rfqId,
                $userId,
                $decisionNote
            );

            $pdo->commit();

            return ApiResponse::success('RFQ expired successfully', $result);

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
    public function open(AcceptRfqRequest $request): array
    {
        return $this->openByData(
            $request->rfqId(),
            $request->decisionNote(),
            $request->userId()
        );
    }

    public function openByData(int $rfqId, ?string $decisionNote, int $userId): array
    {
        $database = App::resolve(Database::class);
        $pdo = $database->connection();

        try {
            $pdo->beginTransaction();

            $result = $this->repository->open($rfqId);

            $pdo->commit();

            $rfq = $this->repository->findById($rfqId);

            if ($rfq && !empty($rfq['supplier_id'])) {
                $this->notifySupplierCompany(
                    (int) $rfq['supplier_id'],
                    'RFQ_ASSIGNED',
                    'Nouvelle RFQ assignée',
                    'Une nouvelle demande de devis vous a été envoyée.',
                    'rfq',
                    $rfqId
                );
            }

            return ApiResponse::success('RFQ opened successfully', $result);

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
    public function allowedActions(string $status): array
    {
        return match ($status) {
            'draft' => ['open'],
            'open' => ['expire'],
            'quoted' => ['accept', 'reject', 'expire'],
            'accepted', 'rejected', 'expired' => [],
            default => []
        };
    }

   public function getAllowedActions(int $rfqId): array
    {
        $rfq = $this->repository->findById($rfqId);

        if (!$rfq) {
            throw new ValidationException([
                'rfq' => ['RFQ not found']
            ]);
        }

        if (Auth::role() === 'fournisseur') {
            $supplierCompanyId = Auth::supplierCompanyId();

            if (!$supplierCompanyId || (int) $rfq['supplier_id'] !== $supplierCompanyId) {
                throw new ValidationException([
                    'auth' => ['You are not allowed to view actions for this RFQ.']
                ]);
            }
        }

        if (Auth::role() !== 'owner' && Auth::role() !== 'fournisseur') {
            throw new ValidationException([
                'auth' => ['You are not allowed to view RFQ actions.']
            ]);
        }

        return ApiResponse::success('Allowed RFQ actions fetched successfully', [
            'rfq_id' => $rfqId,
            'status' => $rfq['status'],
            'allowed_actions' => $this->allowedActionsForRole($rfq['status'], Auth::role())
        ]);
    }

    public function createAutoDraftForLowStock(array $product, int $createdBy): array
    {
        $stockQty = (float) $product['stock_qty'];
        $threshold = (float) $product['low_stock_threshold'];
        $componentId = (int) $product['id']; 

        if ($stockQty > $threshold) {
            return [
                'auto_rfq_created' => false,
                'auto_rfq_id' => null
            ];
        }

        $existing = $this->repository->findActiveForProduct($componentId);

        if ($existing) {
            return [
                'auto_rfq_created' => false,
                'auto_rfq_id' => null,
                'reason' => 'Active RFQ already exists'
            ];
        }

        // NOW you can use it
        $supplierId = $this->repository->findPreferredSupplierForComponent($componentId);

        $quantityNeeded = max($threshold - $stockQty, 1);

        $rfqId = $this->repository->createAutoDraft(
            $componentId,
            $quantityNeeded,
            $createdBy,
            $supplierId
        );

        return [
            'auto_rfq_created' => true,
            'auto_rfq_id' => $rfqId
        ];
    }

    public function create(CreateRfqRequest $request): array
    {
        $database = App::resolve(Database::class);
        $pdo = $database->connection();

        try {
            $pdo->beginTransaction();

            // 🔐 Auth check (keep consistent with your system)
            if (!Session::has('user_id') || !Session::has('role')) {
                throw new ValidationException([
                    'auth' => ['User not authenticated.']
                ]);
            }

            if (Session::get('role') !== 'owner') {
                throw new ValidationException([
                    'role' => ['Only owner can create RFQs.']
                ]);
            }

            $componentId = $request->component_id;
            $supplierId = $request->supplier_id;
            $quantity = $request->quantity_requested;
            $userId = (int) $_SESSION['user_id'];

            // ⚠️ Prevent duplicate active RFQ
            $existing = $this->repository->findActiveForProduct($componentId);

            if ($existing && (!$supplierId || $existing['supplier_id'] == $supplierId)) {
                throw new ValidationException([
                    'component_id' => ['An active RFQ already exists for this component.']
                ]);
            }

            $rfqId = $this->repository->create([
                'component_id' => $componentId,
                'supplier_id' => $supplierId,
                'quantity_requested' => $quantity,
                'status' => 'draft',
                'quoted_price' => null,
                'auto_triggered' => 0,
                'decision_note' => null,
                'client_message' => null,
                'created_by' => $userId
            ]);

            $pdo->commit();

            return ApiResponse::success('RFQ created successfully',[
                    'rfq_id' => $rfqId,
                    'status' => 'draft'
                ]);


        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

   

    public function list(): array
    {
        $role = Auth::role();

        switch ($role) {
            case 'owner':
                $rfqs = $this->repository->listForOwner();
                break;

            case 'fournisseur':
                $supplierCompanyId = Auth::supplierCompanyId();

                if (!$supplierCompanyId) {
                    throw new ValidationException([
                        'supplier_id' => ['Supplier account is not linked to a supplier company.']
                    ]);
                }

                $rfqs = $this->repository->listForSupplier($supplierCompanyId);
                break;

            default:
                throw new ValidationException([
                    'role' => ['Unauthorized']
                ]);
        }
            return ApiResponse::success('RFQs fetched successfully', $rfqs);
    }

    public function detail(int $id): array
    {
        $rfq = $this->repository->findDetailById($id);

        if (!$rfq) {
            throw new ValidationException([
                'rfq' => ['RFQ not found']
            ]);
        }

        if (Auth::role() === 'fournisseur') {
            $supplierCompanyId = Auth::supplierCompanyId();

            if (!$supplierCompanyId || (int) $rfq['supplier_id'] !== $supplierCompanyId) {
                throw new ValidationException([
                    'auth' => ['You are not allowed to view this RFQ.']
                ]);
            }
        }

        if (Auth::role() !== 'owner' && Auth::role() !== 'fournisseur') {
            throw new ValidationException([
                'auth' => ['You are not allowed to view this RFQ.']
            ]);
        }

        return ApiResponse::success('RFQ fetched successfully', $rfq);
    }

    public function quote(int $id, QuoteRfqRequest $request): array
    {
        $rfq = $this->repository->findDetailById($id);

        if (!$rfq) {
            return [
                'error' => 'RFQ not found'
            ];
        }

        if ($rfq['status'] !== 'open') {
            throw new ValidationException([
                'status' => ['Only open RFQs can be quoted.']
            ]);
        }

        $supplierCompanyId = Auth::supplierCompanyId();

        if (!$supplierCompanyId) {
            throw new ValidationException([
                'supplier_id' => ['Supplier account is not linked to a supplier company.']
            ]);
        }

        if ((int) $rfq['supplier_id'] !== $supplierCompanyId) {
            throw new ValidationException([
                'supplier_id' => ['You are not allowed to quote this RFQ.']
            ]);
        }

        $this->repository->updateQuote($id, $request->quoted_price);

        $this->notifyOwner(
        'RFQ_QUOTED',
        'Nouveau devis reçu',
        'Un fournisseur a répondu à une demande de devis.',
        'rfq',
        $id
    );
        return ApiResponse::success('RFQ quoted successfully',[
                'rfq_id' => $id,
                'quoted_price' => $request->quoted_price,
                'status' => 'quoted'
            ]);
    }

    private function createPurchaseLotAfterRfqAccepted(?array $rfq, ?int $createdBy = null): void
    {
        if (!$rfq) {
            throw new ValidationException([
                'rfq' => ['RFQ not found after acceptance.'],
            ]);
        }

        $purchaseLotService = new PurchaseLotService();

        $purchaseLotService->createFromAcceptedRfq($rfq, $createdBy);
    }

    private function allowedActionsForRole(string $status, string $role): array
    {
        if ($role === 'owner') {
            return match ($status) {
                'draft' => ['open'],
                'open' => ['expire'],
                'quoted' => ['accept', 'reject', 'expire'],
                'accepted', 'rejected', 'expired' => [],
                default => []
            };
        }

        if ($role === 'fournisseur') {
            return match ($status) {
                'open' => ['quote'],
                default => []
            };
        }

        return [];
    }

    private function notifyOwner(
        string $type,
        string $title,
        string $message,
        string $referenceType,
        int $referenceId
    ): void {
        $notifications = new NotificationService();

        $notifications->notifyRole(
            'owner',
            $type,
            $title,
            $message,
            $referenceType,
            $referenceId
        );
    }

    private function notifySupplierCompany(
        int $supplierCompanyId,
        string $type,
        string $title,
        string $message,
        string $referenceType,
        int $referenceId
    ): void {
        $userRepository = new UserRepository();

        $supplierUsers = $userRepository->findSupplierUsersByCompanyId($supplierCompanyId);

        $notifications = new NotificationService();

        foreach ($supplierUsers as $supplierUser) {
            $notifications->notifyUser(
                (int) $supplierUser['id'],
                $type,
                $title,
                $message,
                $referenceType,
                $referenceId
            );
        }
    }
}