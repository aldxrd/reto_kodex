<?php

declare(strict_types=1);

namespace Kodex\Controllers;

use Kodex\Domain\Role;
use Kodex\Http\HttpException;
use Kodex\Http\Request;
use Kodex\Http\Response;
use Kodex\Http\Validator;
use Kodex\Repositories\AuditLogRepository;
use Kodex\Repositories\UserRepository;

final class UserController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditLogRepository $audit,
    ) {
    }

    /** GET /api/users?role=agent&status=active&area=Soporte  (admin) */
    public function index(Request $request): Response
    {
        $errors = [];

        $role = null;
        if (($request->query['role'] ?? '') !== '') {
            $role = Role::tryFrom((string) $request->query['role']);
            if ($role === null) {
                $errors['role'] = 'Rol no válido.';
            }
        }

        $status = (string) ($request->query['status'] ?? '');
        $isActive = match ($status) {
            'active' => true,
            'inactive' => false,
            default => null,
        };
        if ($status !== '' && $isActive === null) {
            $errors['status'] = 'Valores permitidos: active, inactive.';
        }

        $area = trim((string) ($request->query['area'] ?? ''));

        if ($errors !== []) {
            throw HttpException::validation($errors);
        }

        $rows = $this->users->list($role !== null ? [$role] : null, isActive: $isActive, area: $area !== '' ? $area : null);

        return Response::data(
            array_map([$this, 'present'], $rows),
            meta: ['counts' => $this->users->countsByRole(), 'areas' => $this->users->areas()],
        );
    }

    /** GET /api/users/agents  (admin, agent) — para el select de "Responsable" */
    public function agents(Request $request): Response
    {
        $rows = $this->users->list([Role::Agent, Role::Admin], onlyActive: true);

        return Response::data(array_map(
            static fn (array $u) => ['id' => (int) $u['id'], 'name' => $u['name'], 'role' => $u['role'], 'area' => $u['area']],
            $rows,
        ));
    }

    /** POST /api/users  (admin) */
    public function store(Request $request): Response
    {
        $v = new Validator($request->json());
        $name = $v->string('name', 2, 120);
        $email = $v->email('email');
        $password = $v->string('password', 8, 72);
        $role = $v->enum('role', Role::class);
        $area = $v->string('area', 2, 80, required: false);
        if ($email !== null && $this->users->emailExists($email)) {
            $v->addError('email', 'Ya existe un usuario con este correo.');
        }
        $v->validate();

        /** @var Role $role */
        $id = $this->users->create((string) $name, (string) $email, password_hash((string) $password, PASSWORD_DEFAULT), $role, $area);
        $this->audit->log($request->user?->id, 'user', $id, 'created', [
            'name' => ['old' => null, 'new' => $name],
            'email' => ['old' => null, 'new' => $email],
            'role' => ['old' => null, 'new' => $role->value],
            'area' => ['old' => null, 'new' => $area],
        ], $request->ip);

        return Response::data($this->present((array) $this->users->findById($id)), 201);
    }

    /** @param array<string, mixed> $u */
    private function present(array $u): array
    {
        $role = Role::from((string) $u['role']);

        return [
            'id' => (int) $u['id'],
            'code' => sprintf('USR-%03d', (int) $u['id']),
            'name' => $u['name'],
            'email' => $u['email'],
            'role' => $role->value,
            'role_label' => $role->label(),
            'area' => $u['area'],
            'is_active' => (bool) $u['is_active'],
            'last_login_at' => $u['last_login_at'],
            'created_at' => $u['created_at'],
        ];
    }
}
