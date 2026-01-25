CREATE TABLE IF NOT EXISTS sy_Log
(
    id         INTEGER PRIMARY KEY,
    created_at INTEGER NOT NULL,
    channel    TEXT    NOT NULL,
    level      TEXT    NOT NULL,
    user       TEXT,
    message    TEXT    NOT NULL,
    context    TEXT
);

CREATE INDEX IF NOT EXISTS idx_log_created_at ON sy_Log(created_at);
CREATE INDEX IF NOT EXISTS idx_log_channel ON sy_Log(channel);
CREATE INDEX IF NOT EXISTS idx_log_level ON sy_Log(level);
CREATE INDEX IF NOT EXISTS idx_log_user ON sy_Log(user);
CREATE INDEX IF NOT EXISTS idx_log_message ON sy_Log(message);
