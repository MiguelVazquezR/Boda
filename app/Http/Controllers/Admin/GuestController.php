<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuestRequest;
use App\Http\Requests\UpdateGuestRequest;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\GuestTable;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class GuestController extends Controller
{
    /**
     * Listado de invitados.
     *
     * Se envía la lista completa para que la tabla (PrimeVue DataTable)
     * filtre, ordene y pagine del lado del cliente sobre todos los registros.
     */
    public function index(Request $request)
    {
        $guests = Guest::with(['group', 'invitation:id,display_name,token'])
            ->orderBy('full_name')
            ->get();

        return Inertia::render('Admin/Guests/Index', [
            'guests' => $guests,
            // Invitaciones digitales (parejas o personas solas) y sus links
            'invitations' => $this->invitations(),
            'filters' => $request->only(['status', 'search']),
            'statusCounts' => [
                'pending' => Guest::byStatus('pending')->count(),
                'confirmed' => Guest::byStatus('confirmed')->count(),
                'declined' => Guest::byStatus('declined')->count(),
                'total' => Guest::count(),
            ],
        ]);
    }

    /**
     * Formulario para crear un invitado.
     */
    public function create()
    {
        return Inertia::render('Admin/Guests/Create', [
            'groups' => GuestGroup::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Formulario para editar un invitado.
     */
    public function edit(Guest $guest)
    {
        return Inertia::render('Admin/Guests/Edit', [
            'guest' => $guest->load('group'),
            'groups' => GuestGroup::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Alta manual de un invitado.
     */
    public function store(StoreGuestRequest $request)
    {
        $guest = Guest::create($request->validated());

        GuestTable::ensureExists($guest->table_group);

        return back()->with('success', 'Invitado agregado correctamente.');
    }

    /**
     * Edición de un invitado.
     */
    public function update(UpdateGuestRequest $request, Guest $guest)
    {
        $guest->update($request->validated());

        GuestTable::ensureExists($guest->table_group);

        return back()->with('success', 'Invitado actualizado correctamente.');
    }

    /**
     * Eliminación de un invitado.
     */
    public function destroy(Guest $guest)
    {
        $guest->delete();

        return back()->with('success', 'Invitado eliminado correctamente.');
    }

    /**
     * Importación masiva de invitados vía CSV.
     *
     * Columnas soportadas (en inglés o español, sin distinguir mayúsculas ni acentos):
     *   - first_name | nombre            (obligatorio, o bien full_name)
     *   - last_name  | apellidos
     *   - full_name  | nombre_completo   (alternativa a first_name + last_name)
     *   - age        | edad
     *   - gender     | genero / sexo     (femenino/masculino; acepta F/M, mujer/hombre, etc.)
     *   - group      | grupo / guest_group / guest_group_id
     *   - phone      | celular / telefono
     *   - origin     | origen            (local/foraneo; tolera acentos)
     *   - state      | estado
     *   - city       | ciudad
     *   - table_group| mesa
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'extensions:csv,txt', 'max:2048'],
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return back()->with('error', 'El archivo CSV está vacío.');
        }

        // Normalizar a UTF-8: Excel suele guardar con Windows-1252, CP850/CP437 o ISO-8859-1
        $content = $this->convertToUtf8($content);

        // Stream en memoria para usar fgetcsv (maneja comillas y saltos de línea internos)
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return back()->with('error', 'El archivo CSV está vacío o no tiene encabezados.');
        }
        rewind($handle);

        // Detectar delimitador (soporta coma, punto y coma, tabulador o pipe)
        $delimiter = $this->detectDelimiter($firstLine);

        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false || array_filter($headers, fn ($h) => trim((string) $h) !== '') === []) {
            fclose($handle);
            return back()->with('error', 'El archivo CSV está vacío o no tiene encabezados.');
        }

        // Normalizar encabezados a claves canónicas (inglés/español, sin acentos)
        $headers = array_map(fn ($header) => $this->canonicalHeader((string) $header), $headers);

        if (! in_array('first_name', $headers, true) && ! in_array('full_name', $headers, true)) {
            fclose($handle);
            return back()->with('error', 'El archivo debe contener la columna "first_name" (o "full_name").');
        }

        // Mapa de grupos existentes por nombre normalizado
        $groupsByName = GuestGroup::all()
            ->mapWithKeys(fn (GuestGroup $group) => [$this->normalizeText($group->name) => $group->id])
            ->all();

        $created = 0;
        $errors = 0;
        $groupsCreated = 0;
        $tablesUsed = []; // nombres de mesa mencionados en el CSV
        $skipped = [];
        $rowNumber = 1; // la fila 1 son los encabezados

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            // Ignorar filas completamente vacías
            if (array_filter($row, fn ($value) => trim((string) $value) !== '') === []) {
                continue;
            }

            // Filas con distinto número de columnas que los encabezados: se omiten sin romper la importación
            if (count($row) !== count($headers)) {
                $errors++;
                $skipped[] = "fila {$rowNumber}: número de columnas incorrecto";
                continue;
            }

            $data = array_combine($headers, $row);

            $firstName = trim((string) ($data['first_name'] ?? ''));
            $lastName = trim((string) ($data['last_name'] ?? ''));
            $fullName = trim((string) ($data['full_name'] ?? ''));

            // Compatibilidad: acepta first_name/last_name o full_name (se separa en la primera palabra)
            if ($firstName === '' && $fullName !== '') {
                $parts = preg_split('/\s+/', $fullName, 2);
                $firstName = $parts[0] ?? '';
                $lastName = $parts[1] ?? '';
            }

            if ($firstName === '') {
                $errors++;
                $skipped[] = "fila {$rowNumber}: falta el nombre (first_name)";
                continue;
            }

            $tableGroup = $this->cleanValue($data['table_group'] ?? null);

            Guest::create([
                'first_name' => $firstName,
                'last_name' => $lastName !== '' ? $lastName : null,
                'age' => $this->parseAge($data['age'] ?? null),
                'gender' => $this->normalizeGender($data['gender'] ?? null),
                'guest_group_id' => $this->resolveGroupId($data, $groupsByName, $groupsCreated),
                'phone' => $this->normalizePhone($data['phone'] ?? null),
                'origin' => $this->normalizeOrigin($data['origin'] ?? null),
                'state' => $this->cleanValue($data['state'] ?? null),
                'city' => $this->cleanValue($data['city'] ?? null),
                'table_group' => $tableGroup,
            ]);

            // Las mesas mencionadas en el CSV quedan registradas para poder gestionarlas.
            if ($tableGroup !== null) {
                $tablesUsed[$tableGroup] = true;
            }

            $created++;
        }

        fclose($handle);

        foreach (array_keys($tablesUsed) as $tableName) {
            GuestTable::ensureExists($tableName);
        }

        $message = "Importación completada: {$created} invitado(s) creado(s).";
        if ($groupsCreated > 0) {
            $message .= " Se crearon {$groupsCreated} grupo(s) nuevo(s).";
        }
        if ($errors > 0) {
            $message .= " {$errors} fila(s) omitida(s): ".implode('; ', array_slice($skipped, 0, 5))
                .(count($skipped) > 5 ? '...' : '');
        }

        if ($created === 0 && $errors > 0) {
            return back()->with('error', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Convierte el contenido a UTF-8.
     *
     * - Quita el BOM UTF-8 que Excel agrega al guardar como CSV UTF-8.
     * - Si ya es UTF-8 válido, lo devuelve tal cual.
     * - Si no, detecta la codificación ANSI (Windows-1252, ISO-8859-1, CP850)
     *   eligiendo la que produzca más caracteres acentuados en español.
     */
    private function convertToUtf8(string $content): string
    {
        // UTF-16 con BOM (Excel: "Texto Unicode")
        if (str_starts_with($content, "\xFF\xFE")) {
            return mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        }
        if (str_starts_with($content, "\xFE\xFF")) {
            return mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        }

        // Quitar BOM UTF-8
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        if ($content === '' || mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        // Solo usar codificaciones realmente soportadas por mbstring
        $supported = array_map('strtolower', mb_list_encodings());
        $candidates = ['Windows-1252', 'ISO-8859-1', 'CP850'];
        $best = null;
        $bestScore = -1;

        foreach ($candidates as $encoding) {
            if (! in_array(strtolower($encoding), $supported, true)) {
                continue;
            }

            try {
                $converted = mb_convert_encoding($content, 'UTF-8', $encoding);
            } catch (\ValueError) {
                continue;
            }

            if ($converted === false) {
                continue;
            }

            $score = preg_match_all('/[áéíóúñüÁÉÍÓÚÑÜ]/u', $converted);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $converted;
            }
        }

        return $best ?? $content;
    }

    /**
     * Detecta el delimitador más probable (coma, punto y coma, tabulador o pipe).
     */
    private function detectDelimiter(string $line): string
    {
        // Quitar campos entre comillas para no contar delimitadores internos
        $line = preg_replace('/"([^"]|"")*"/', '', $line);
        $candidates = [',', ';', "\t", '|'];
        $best = ',';
        $bestCount = -1;

        foreach ($candidates as $candidate) {
            $count = substr_count($line, $candidate);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * Normaliza un encabezado a una clave canónica (inglés o español, sin acentos).
     */
    private function canonicalHeader(string $header): string
    {
        $normalized = $this->normalizeText($header);

        $aliases = [
            'first_name' => 'first_name', 'nombre' => 'first_name', 'nombres' => 'first_name',
            'last_name' => 'last_name', 'apellido' => 'last_name', 'apellidos' => 'last_name',
            'full_name' => 'full_name', 'nombre_completo' => 'full_name', 'nombrecompleto' => 'full_name',
            'age' => 'age', 'edad' => 'age',
            'gender' => 'gender', 'genero' => 'gender', 'sexo' => 'gender',
            'group' => 'group', 'grupo' => 'group', 'guest_group' => 'group', 'guest_group_id' => 'group',
            'phone' => 'phone', 'celular' => 'phone', 'telefono' => 'phone', 'movil' => 'phone', 'cel' => 'phone',
            'origin' => 'origin', 'origen' => 'origin',
            'state' => 'state', 'estado' => 'state',
            'city' => 'city', 'ciudad' => 'city',
            'table_group' => 'table_group', 'mesa' => 'table_group', 'mesa_asignada' => 'table_group', 'mesaasignada' => 'table_group',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    /**
     * Limpia un valor opcional (trim). Devuelve null si está vacío.
     */
    private function cleanValue($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Normaliza un string para comparaciones: minúsculas y sin acentos.
     */
    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        $unwanted = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o',
        ];

        return strtr($value, $unwanted);
    }

    /**
     * Parsea la edad como entero entre 1 y 120. Devuelve null si no es válida.
     */
    private function parseAge($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        $age = (int) round((float) $value);

        return ($age >= 1 && $age <= 120) ? $age : null;
    }

    /**
     * Normaliza el género a femenino|masculino (acepta F/M, mujer/hombre, etc.).
     */
    private function normalizeGender($value): ?string
    {
        $value = $this->normalizeText((string) $value);
        if ($value === '') {
            return null;
        }

        return match ($value) {
            'f', 'fem', 'femenino', 'mujer', 'mujeres' => 'femenino',
            'm', 'mas', 'masculino', 'hombre', 'hombres', 'h' => 'masculino',
            default => null,
        };
    }

    /**
     * Normaliza el origen a local|foraneo (tolera acentos: "Foráneo" → foraneo).
     */
    private function normalizeOrigin($value): ?string
    {
        $value = $this->normalizeText((string) $value);
        if ($value === '') {
            return null;
        }

        return match ($value) {
            'l', 'local', 'locales' => 'local',
            'f', 'foraneo', 'foraneos', 'externo', 'externos' => 'foraneo',
            default => null,
        };
    }

    /**
     * Normaliza el teléfono: solo dígitos, máximo 10 (la columna es string(10)).
     */
    private function normalizePhone($value): ?string
    {
        $value = preg_replace('/\D/', '', (string) $value);

        return $value === '' ? null : substr($value, 0, 10);
    }

    /**
     * Resuelve el grupo del invitado a partir de la columna group/grupo/guest_group.
     * Acepta un ID numérico o el nombre del grupo (se crea automáticamente si no existe).
     */
    private function resolveGroupId(array $data, array &$groupsByName, int &$groupsCreated): ?int
    {
        $value = trim((string) ($data['group'] ?? ''));
        if ($value === '') {
            return null;
        }

        // Si es un ID numérico y el grupo existe
        if (ctype_digit($value) && GuestGroup::whereKey((int) $value)->exists()) {
            return (int) $value;
        }

        // Buscar por nombre (sin distinguir mayúsculas ni acentos)
        $key = $this->normalizeText($value);
        if (isset($groupsByName[$key])) {
            return $groupsByName[$key];
        }

        // Crear el grupo si no existe
        $group = GuestGroup::create(['name' => $value]);
        $groupsByName[$key] = $group->id;
        $groupsCreated++;

        return $group->id;
    }

    /**
     * Invitaciones digitales con sus miembros, links y estado de confirmación.
     * Se envían a la vista de invitados para gestionarlas desde «Parejas / Links».
     */
    private function invitations(): Collection
    {
        return Invitation::with('members:id,first_name,full_name,invitation_id,rsvp_status')
            ->orderBy('display_name')
            ->get()
            ->map(fn (Invitation $invitation) => [
                'id' => $invitation->id,
                'token' => $invitation->token,
                'display_name' => $invitation->display_name,
                'public_url' => $invitation->publicUrl(),
                'rsvp_url' => $invitation->rsvpUrl(),
                'whatsapp_url' => $invitation->whatsappUrl(),
                'pending_count' => $invitation->members->where('rsvp_status', 'pending')->count(),
                'members' => $invitation->members->map(fn (Guest $guest) => [
                    'id' => $guest->id,
                    'full_name' => $guest->full_name,
                ])->values(),
            ])
            ->values();
    }
}
