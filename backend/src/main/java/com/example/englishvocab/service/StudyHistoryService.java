package com.example.englishvocab.service;

import com.example.englishvocab.dto.ApiDtos.SessionEnd;
import com.example.englishvocab.dto.ApiDtos.SessionStart;
import com.example.englishvocab.entity.StudyHistory;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.repository.StudyHistoryRepository;
import com.example.englishvocab.repository.TopicRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.List;

@Service
@Transactional
public class StudyHistoryService {
    private final StudyHistoryRepository sessionRepository;
    private final TopicRepository topicRepository;

    public StudyHistoryService(StudyHistoryRepository sessionRepository, TopicRepository topicRepository) {
        this.sessionRepository = sessionRepository;
        this.topicRepository = topicRepository;
    }

    @Transactional
    public StudyHistory start(User user, SessionStart req) {
        StudyHistory s = new StudyHistory();
        s.setUser(user);
        if (req.topicId() != null) {
            s.setTopic(topicRepository.findById(req.topicId()).orElse(null));
        }
        s.setStudyMode(req.studyMode() == null ? "FLASHCARD" : req.studyMode());
        return sessionRepository.save(s);
    }

    @Transactional
    public StudyHistory end(User user, Integer sessionId, SessionEnd req) {
        StudyHistory s = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Không tìm thấy phiên học"));
        if (!s.getUser().getUserID().equals(user.getUserID())) {
            throw new IllegalArgumentException("Không phải phiên của bạn");
        }
        s.setEndedAt(LocalDateTime.now());
        s.setItemsStudied(req.itemsStudied());
        s.setCorrectCount(req.correctCount());
        s.setWrongCount(req.wrongCount());
        return sessionRepository.save(s);
    }

    public List<StudyHistory> mine(User user) {
        return sessionRepository.findByUser_UserIDOrderByStartedAtDesc(user.getUserID());
    }
}
