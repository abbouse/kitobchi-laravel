import { Fragment, useMemo, useState } from 'react';

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
      <p className="mb-0 f-s-15 f-w-500 txt-ellipsis-1 pagination-info">{from}-{to} / {total}</p>
      {/* Axelit: "pagination app-pagination" */}
      <ul className="pagination app-pagination justify-content-end">
        <li className={`page-item ${page <= 1 ? 'disabled' : ''}`}>
          <button type="button" className="page-link" disabled={page <= 1} onClick={() => onPageChange(page - 1)} aria-label="Oldingi sahifa">
            <i className="ti ti-chevron-left"></i>
          </button>
        </li>
        {pages.map((item, index) => {
          const prev = pages[index - 1];
          return (
            <Fragment key={item}>
              {prev && item - prev > 1 ? (
                <li className="page-item disabled gap"><span className="page-link">…</span></li>
              ) : null}
              <li className={`page-item ${item === page ? 'active' : ''}`}>
                <button type="button" className="page-link" onClick={() => onPageChange(item)} aria-current={item === page ? 'page' : undefined}>
                  {item}
                </button>
              </li>
            </Fragment>
          );
        })}
        <li className={`page-item ${page >= totalPages ? 'disabled' : ''}`}>
          <button type="button" className="page-link" disabled={page >= totalPages} onClick={() => onPageChange(page + 1)} aria-label="Keyingi sahifa">
            <i className="ti ti-chevron-right"></i>
          </button>
        </li>
      </ul>
    </div>
  );
}
