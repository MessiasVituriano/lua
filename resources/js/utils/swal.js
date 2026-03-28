import Swal from 'sweetalert2';

const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';

const defaults = () => ({
    background: isDark() ? '#1e1f2e' : '#fff',
    color: isDark() ? '#e5e7eb' : '#1f2937',
    confirmButtonColor: '#6f42c1',
    cancelButtonColor: '#6b7280',
    customClass: { popup: 'rounded-3' },
});

export function swalSuccess(title, text = '') {
    return Swal.fire({ ...defaults(), icon: 'success', title, text, timer: 2500, showConfirmButton: false });
}

export function swalError(title, text = '') {
    return Swal.fire({ ...defaults(), icon: 'error', title, text });
}

export function swalInfo(title, text = '') {
    return Swal.fire({ ...defaults(), icon: 'info', title, text, timer: 3000, showConfirmButton: false });
}

export function swalWarning(title, text = '') {
    return Swal.fire({ ...defaults(), icon: 'warning', title, text, timer: 3000, showConfirmButton: false });
}

export async function swalConfirmDanger(title, text = '') {
    const result = await Swal.fire({
        ...defaults(),
        icon: 'warning',
        title,
        text,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sim, remover',
        cancelButtonText: 'Cancelar',
    });
    return result.isConfirmed;
}

export async function swalConfirmSuccess(title, text = '') {
    const result = await Swal.fire({
        ...defaults(),
        icon: 'question',
        title,
        text,
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Sim, confirmar',
        cancelButtonText: 'Cancelar',
    });
    return result.isConfirmed;
}

export async function swalConfirmInfo(title, text = '') {
    const result = await Swal.fire({
        ...defaults(),
        icon: 'info',
        title,
        text,
        showCancelButton: true,
        confirmButtonText: 'Sim',
        cancelButtonText: 'Cancelar',
    });
    return result.isConfirmed;
}
