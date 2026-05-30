import { useMemo, useState } from 'react';

export function useClientPagination<T>(items: T[], perPage = 24) {
  const [page, setPage] = useState(1);
  const totalPages = Math.max(1, Math.ceil(items.length / perPage));
  const safePage = Math.min(page, totalPages);

  const paginated = useMemo(() => {
    const start = (safePage - 1) * perPage;
    return items.slice(start, start + perPage);
  }, [items, perPage, safePage]);

  const from = items.length ? (safePage - 1) * perPage + 1 : 0;
  const to = Math.min(safePage * perPage, items.length);

  return { page: safePage, setPage, totalPages, paginated, from, to, total: items.length };
}

export default function PaginationControls({
  page,
  totalPages,
  from,
  to,
  total,
  onPageChange,
}: {
  page: number;
  totalPages: number;
  from: number;
  to: number;
  total: number;
  onPageChange: (page: number) => void;
}) {
  if (totalPages <= 1) {
    return null;
  }

  const pages = Array.from({ length: totalPages }, (_, index) => index + 1)
    .filter((item) => item === 1 || item === totalPages || Math.abs(item - page) <= 1);

  return (
    <div className="pagination-bar">
      <div className="text-muted small">{from}-{to} / {total}</div>
      <div className="pagination-buttons">
        <button className="btn btn-sm btn-light" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>
          <i className="bi bi-chevron-left"></i>
        </button>
        {pages.map((item, index) => {
          const prev = pages[index - 1];
          return (
            <span key={item} className="d-inline-flex align-items-center gap-1">
              {prev && item - prev > 1 ? <span className="text-muted px-1">...</span> : null}
              <button
                className={`btn btn-sm ${item === page ? 'btn-primary-gradient' : 'btn-light'}`}
                onClick={() => onPageChange(item)}
              >
                {item}
              </button>
            </span>
          );
        })}
        <button className="btn btn-sm btn-light" disabled={page >= totalPages} onClick={() => onPageChange(page + 1)}>
          <i className="bi bi-chevron-right"></i>
        </button>
      </div>
    </div>
  );
}
