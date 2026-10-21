<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Services\ContractGenerator;
use App\Support\Contracts\ContractTemplates;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Boshqaruv → Shartnomalar: rekvizitlar, shartlar va hujjat yaratish. */
class ContractsController extends Controller
{
    public function __construct(private ContractGenerator $contracts)
    {
    }

    public function index(): Response
    {
        $stored = $this->contracts->stored();
        $field = fn (string $scope, string $key, array $f) => [
            'key' => $key, 'label' => $f['label'], 'default' => $f['default'], 'hint' => $f['hint'] ?? null,
            'value' => $stored[$scope][$key] ?? '',
        ];

        $company = [];
        foreach (ContractTemplates::companyFields() as $key => $f) {
            $company[] = $field(ContractTemplates::COMPANY_SCOPE, $key, $f);
        }
        $templates = [];
        foreach (ContractTemplates::all() as $slug => $t) {
            $templates[] = [
                'slug' => $slug,
                'title' => $t['title'],
                'description' => $t['description'],
                'is_public' => $this->contracts->isPublic($slug),
                'public_url' => url('/api/v1/kitobchi/contracts/'.$slug.'/download'),
                'terms' => array_map(fn ($k) => $field($slug, $k, $t['terms'][$k]), array_keys($t['terms'])),
                'party' => array_map(fn ($k) => ['key' => $k, 'label' => $t['party'][$k]['label']], array_keys($t['party'])),
            ];
        }

        return Inertia::render('Shartnomalar', ['company' => $company, 'templates' => $templates]);
    }

    public function saveCompany(Request $request): RedirectResponse
    {
        $this->contracts->save(ContractTemplates::COMPANY_SCOPE, $this->pick($request, array_keys(ContractTemplates::companyFields())), $this->adminId($request));

        return back()->with('success', 'Rekvizitlar saqlandi');
    }

    public function saveTemplate(Request $request, string $slug): RedirectResponse
    {
        $tpl = ContractTemplates::get($slug) ?? abort(404);
        $values = $this->pick($request, array_keys($tpl['terms']));
        $values[ContractGenerator::VISIBILITY_KEY] = $request->boolean('is_public') ? '1' : '0';
        $this->contracts->save($slug, $values, $this->adminId($request));

        return back()->with('success', 'Shartnoma shartlari saqlandi');
    }

    /** Aniq kontragent uchun hujjat (kontragent ma'lumotlari saqlanmaydi). */
    public function generate(Request $request, string $slug): HttpResponse
    {
        $tpl = ContractTemplates::get($slug) ?? abort(404);
        $data = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'party' => 'nullable|array',
            'party.*' => 'nullable|string|max:300',
        ]);
        $date = ! empty($data['date'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $data['date'], 'Asia/Tashkent')
            : CarbonImmutable::now('Asia/Tashkent');
        $party = array_intersect_key((array) ($data['party'] ?? []), $tpl['party']);
        $docx = $this->contracts->render($slug, $this->contracts->values($slug, $date, $party));

        return response($docx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$this->contracts->filename($slug, $date).'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function pick(Request $request, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $v = $request->input($key);
            if ($v !== null && ! is_string($v)) {
                abort(422);
            }
            $out[$key] = $v === null ? null : mb_substr($v, 0, 500);
        }

        return $out;
    }

    private function adminId(Request $request): ?int
    {
        return $request->user('panel')?->id;
    }
}
