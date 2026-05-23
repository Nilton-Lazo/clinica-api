@php
    $inst = $meta->institution;
@endphp
<table width="100%" cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse;">
    <tr>
        <td valign="top" style="padding:0; font-size:7.5px; color:#64748b; vertical-align:top; width:40%; border:none;">
            <strong style="color:#334155;">{{ $inst->name }}</strong><br>
            Documento generado por el sistema clínico
        </td>
        <td valign="middle" style="padding:0; font-size:7.5px; color:#64748b; text-align:center; vertical-align:middle; width:20%; border:none;">
            Uso interno / admisión
        </td>
        <td valign="top" style="padding:0; font-size:7.5px; color:#64748b; text-align:right; vertical-align:top; width:40%; border:none;">
            <span class="tabular">Generado: {{ $meta->generatedAt }}</span><br>
            Usuario: {{ $meta->generatedBy }}
        </td>
    </tr>
</table>
