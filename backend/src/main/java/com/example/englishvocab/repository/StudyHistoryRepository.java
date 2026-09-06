package com.example.englishvocab.repository;

import com.example.englishvocab.entity.StudyHistory;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface StudyHistoryRepository extends JpaRepository<StudyHistory, Integer> {
    List<StudyHistory> findByUser_UserIDOrderByStartedAtDesc(Integer userId);
}
